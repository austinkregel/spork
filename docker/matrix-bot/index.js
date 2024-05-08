import {
    MatrixClient,
    MatrixAuth,
    AutojoinRoomsMixin,
    AutojoinUpgradedRoomsMixin,
    SimpleFsStorageProvider,
    RustSdkCryptoStorageProvider,
    RustSdkAppserviceCryptoStorageProvider,
    Appservice,
    SimpleRetryJoinStrategy,
    EncryptionAlgorithm,
    ConsoleLogger,
} from "matrix-bot-sdk";
import { StoreType } from "@matrix-org/matrix-sdk-crypto-nodejs";
import dotenv from 'dotenv';
import Sequelize, {DataTypes, QueryTypes} from "sequelize";
import fs from 'fs';
import ora from 'ora';
import dayjs from 'dayjs';
import crypto from "crypto";

dotenv.config();

const storageProvider = new SimpleFsStorageProvider("./docker/matrix-bot/bot.json"); // or any other IStorageProvider
const cryptoProvider = new RustSdkCryptoStorageProvider("./docker/matrix-bot/db", StoreType.Sled);
// In order to sync room keys, we need to ensure we can trust the devices we're getting them from.
// We can request the list of devices, and verify their keys.
const sequelize = new Sequelize(
    process.env.DB_DATABASE,
    process.env.DB_USERNAME,
    process.env.DB_PASSWORD,
    {
        host: '127.0.0.1',
        dialect: process.env.DB_CONNECTION,
        logging: false,
    }
);


// Since we're talking directly with our database, Laravel isn't getting any of the events
//   Nothing in our UI will update, and no php code will trigger.
// We need a way to hook

async function findOrCreateMessageEvent(Threads, Messages, People, thread, message, credential, is_decrypted = false) {
    if (message.type !== 'm.room.message') {
        console.log('Told this was a message, but reporeted as ', message.type)
        return null;
    }
    let threadInQuestion = await Threads.findOne({
        where: {thread_id: thread}
    });

    if (!threadInQuestion?.name) {
        console.error('Thread doesn\'t exist yet', thread);

        threadInQuestion = await Threads.create({
            thread_id: thread,
            name: thread,
            origin_server_ts: dayjs(),
        });
    }

    const messageToCreate = await Messages.findOne({
        where: {event_id: message.event_id,}
    });

    if (!messageToCreate?.event_id) {
        const thread_id = threadInQuestion.id
        const dmTarget = '@kregel:communication.ventures';

        const sender = await findPersonByIdentifier(People, message);

        const messageModel = await Messages.create({
            ...(message?.content?.info?.thumbnail_url !== undefined ? {
                thumbnail_url: message.content.info.thumbnail_url,
            } : {}),
            ...(message?.content?.settings ? message?.content?.settings : {}),
            from_person: sender?.id,
            to_person: (await findPersonByIdentifier(People, {
                sender: dmTarget
            })).id,
            thread_id,
            type: message.type,
            originated_at: dayjs(message.origin_server_ts).format('YYYY-MM-DD HH:mm:ss'),
            message: message.content.body,
            event_id: message.event_id,
            html_message: message?.content?.format_body,
            is_decrypted: true,
            credential_id: credential.id,
        })

        const updatedTime = {origin_server_ts: dayjs()};

        await Threads.update(updatedTime, {
            where: {thread_id: thread}
        });

        return messageModel;
    }
}

async function findPersonByIdentifier(People, message) {
    const identifier = message.sender;

    let peoples = await sequelize.query('select * from people where JSON_CONTAINS(identifiers, :identifier, \'$\')', {
        type: QueryTypes.SELECT,
        replacements: {
            identifier: JSON.stringify(identifier)
        },
    })

    const person = peoples[0];

    if (peoples && !person) {
        const person = await People.create({
            name: message.content.displayname ?? identifier,
            identifiers: JSON.stringify([identifier])
        });

        peoples = await sequelize.query('select * from people where JSON_CONTAINS(identifiers, :identifier, \'$\')', {
            type: QueryTypes.SELECT,
            replacements: {
                identifier: JSON.stringify(identifier)
            },
        });
    }


    return person ?? null;
}

const setupSequlize = async () => {
    console.log('[-] Sequilize authenticating...');
    await sequelize.authenticate();
    console.log('[-] Sequilize authenticated...');

    const Threads = sequelize.define('threads', {
        id: {
            type: DataTypes.BIGINT,
            autoIncrement: true,
            primaryKey: true,
        },
        name: {
            type: DataTypes.STRING,
        },
        description: {
            type: DataTypes.STRING,
        },
        rules: {
            type: DataTypes.STRING,
        },
        topic: {
            type: DataTypes.STRING,
        },
        thread_id: {
            type: DataTypes.STRING,
        },
        origin_server_ts: {
            type: DataTypes.DATE,
        },
        settings: {
            type: DataTypes.TEXT,
        },
        created_at: {
            type: DataTypes.DATE,
        },
        updated_at: {
            type: DataTypes.DATE,
        }
    }, {
        updatedAt: 'updated_at',
        createdAt: 'created_at',
    })

    const Messages = sequelize.define('messages', {
        id: {
            type: DataTypes.BIGINT,
            autoIncrement: true,
            primaryKey: true,
        },
        credential_id: {
            type: DataTypes.BIGINT,
        },
        from_person: {
            type: DataTypes.BIGINT,
        },
        to_person: {
            type: DataTypes.BIGINT,
        },
        type: {
            type: DataTypes.STRING,
        },
        event_id: {
            type: DataTypes.STRING,
        },
        thread_id: {
            type: DataTypes.BIGINT,
        },
        is_decrypted: {
            type: DataTypes.BOOLEAN,
        },
        spam: {
            type: DataTypes.BOOLEAN,
        },
        seen: {
            type: DataTypes.BOOLEAN,
        },
        answered: {
            type: DataTypes.BOOLEAN,
        },
        thumbnail_url: {
            type: DataTypes.STRING(2048),
        },
        originated_at: {
            type: DataTypes.TIME,
        },
        message: {
            type: DataTypes.TEXT
        },
        subject: {
            type: DataTypes.TEXT
        },
        settings: {
            type: DataTypes.JSON
        },
        html_message: {
            type: DataTypes.TEXT,
        },
        created_at: {
            type: DataTypes.DATE,
        },
        updated_at: {
            type: DataTypes.DATE,
        }
    }, {
        updatedAt: 'updated_at',
        createdAt: 'created_at',
    })

    const People = sequelize.define('people', {
        id: {
            type: DataTypes.BIGINT.UNSIGNED,
            primaryKey: true,
            autoIncrement: true,
        },
        name: {
            type: DataTypes.STRING(255),
            allowNull: false,
        },
        primary_number: DataTypes.STRING(255),
        primary_address: DataTypes.STRING(255),
        primary_email: DataTypes.STRING(255),
        pronouns: DataTypes.STRING(255),
        birthdate: DataTypes.DATE,
        phone_numbers: {
            type: DataTypes.TEXT,
            validate: {
                isJson: function (value) {
                    try {
                        JSON.parse(value);
                    } catch (error) {
                        throw new Error('Invalid JSON format for phone_numbers');
                    }
                },
            },
        },
        addresses: {
            type: DataTypes.TEXT,
            validate: {
                isJson: function (value) {
                    try {
                        JSON.parse(value);
                    } catch (error) {
                        throw new Error('Invalid JSON format for addresses');
                    }
                },
            },
        },
        emails: {
            type: DataTypes.TEXT,
            validate: {
                isJson: function (value) {
                    try {
                        JSON.parse(value);
                    } catch (error) {
                        throw new Error('Invalid JSON format for emails');
                    }
                },
            },
        },
        estimated_home_value: DataTypes.STRING(255),
        estimated_income: DataTypes.STRING(255),
        education: {
            type: DataTypes.TEXT,
            validate: {
                isJson: function (value) {
                    try {
                        JSON.parse(value);
                    } catch (error) {
                        throw new Error('Invalid JSON format for education');
                    }
                },
            },
        },
        jobs: {
            type: DataTypes.TEXT,
            validate: {
                isJson: function (value) {
                    try {
                        JSON.parse(value);
                    } catch (error) {
                        throw new Error('Invalid JSON format for jobs');
                    }
                },
            },
        },
        locality: {
            type: DataTypes.TEXT,
            validate: {
                isJson: function (value) {
                    try {
                        JSON.parse(value);
                    } catch (error) {
                        throw new Error('Invalid JSON format for locality');
                    }
                },
            },
        },
        identifiers: {
            type: DataTypes.TEXT,
            validate: {
                isJson: function (value) {
                    try {
                        JSON.parse(value);
                    } catch (error) {
                        throw new Error('Invalid JSON format for identifiers');
                    }
                },
            },
        },
        names: {
            type: DataTypes.TEXT,
            validate: {
                isJson: function (value) {
                    try {
                        JSON.parse(value);
                    } catch (error) {
                        throw new Error('Invalid JSON format for names');
                    }
                },
            },
        },
        created_at: DataTypes.DATE,
        updated_at: DataTypes.DATE,
    }, {
        timestamps: true, // Set to true if you want Sequelize to manage timestamps
        tableName: 'people',
        updatedAt: 'updated_at',
        createdAt: 'created_at',
        collate: 'utf8mb4_unicode_ci',
    });

    const Participant = sequelize.define('thread_participants', {
        id: {
            type: DataTypes.BIGINT.UNSIGNED,
            primaryKey: true,
            autoIncrement: true,
        },

        person_id: {
            type: DataTypes.BIGINT.UNSIGNED,
        },
        thread_id: {
            type: DataTypes.BIGINT.UNSIGNED,
        },

        joined_at: {
            type: DataTypes.DATE,
        },
        created_at: DataTypes.DATE,
        updated_at: DataTypes.DATE,
    }, {
        timestamps: true,
        tableName: 'thread_participants',
        updatedAt: 'updated_at',
        createdAt: 'created_at',
    })

    const Credential = sequelize.define('credentials', {
        id: {
            type: DataTypes.BIGINT.UNSIGNED,
            primaryKey: true,
            autoIncrement: true,
        },
        user_id: DataTypes.BIGINT,
        name: DataTypes.STRING(255),
        type: DataTypes.STRING(255),
        service: DataTypes.STRING(255),

        api_key: DataTypes.STRING(2048),
        secret_key: DataTypes.STRING(2048),
        access_token: DataTypes.STRING(2048),
        refresh_token: DataTypes.STRING(2048),
        settings: DataTypes.STRING(2048),

        enabled_on: DataTypes.DATE,
        created_at: DataTypes.DATE,
        updated_at: DataTypes.DATE,
    }, {
        timestamps: true,
        updatedAt: 'updated_at',
        createdAt: 'created_at',
    })
    console.log('[-] Sequilize models defined...');

    return {
        Threads,
        Messages,
        People,
        Participant,
        Credential,
    }
}

async function main() {
    try {
        const {
            Threads,
            Messages,
            People,
            Participant,
            Credential,
        } = await setupSequlize();

        let credential = await Credential.findOne({
            where: {
                type: 'matrix',
            }
        });
        let client;
        if (!credential?.type) {
            const auth = new MatrixAuth(process.env.MATRIX_HOST);
            client = await auth.passwordLogin(process.env.MATRIX_USERNAME, process.env.MATRIX_PASSWORD);
            const { user_id, device_id } = await client.getWhoAmI();

            credential = await Credential.create({
                name: device_id,
                type: 'matrix',
                service: 'matrix',
                'access_token': client.accessToken,
                enabled_on: dayjs(),
                user_id: 1,
            });
        } else {
            client = new MatrixClient(
                process.env.MATRIX_HOST,
                credential.access_token,
                storageProvider,
                cryptoProvider,
            )
        }


        const { user_id, device_id } = await client.getWhoAmI();
        console.log('[-]   #############################');
        console.log('[-]   Authenticated as', user_id, device_id);
        console.log('[-]   #############################');

        if (!await storageProvider.isUserRegistered(user_id)) {
            storageProvider.addRegisteredUser(user_id);
        }

        // const client = await auth.passwordLogin(userId, password, "Programmatic Access");
        console.log('[-] Autojoin rooms mixin...');
        AutojoinRoomsMixin.setupOnClient(client);
        AutojoinUpgradedRoomsMixin.setupOnClient(client);
        console.log('[-] Autojoin rooms mixin established..');

        client.on('room.join', async (event) => {
            await client.crypto.onRoomJoin(event.room_id);
        })

        client.on("room.message", async (roomId, messageEvent) => {
            // await client.upload
            console.log(roomId, 'received a new message from', messageEvent?.sender)
            await findOrCreateMessageEvent(Threads, Messages, People, roomId, messageEvent, credential, true);
        });
        // console.log('[-] Preparing crypto lib');
        // const cryptoSpinner = ora('Syncing joined rooms, and preparing encryption').start();
        /** @var IStorageProvider userStorage **/
        // const userStorage = await client.storageProvider.storageForUser(user_id)

        const rooms = await client.getJoinedRooms();
        await client.crypto.prepare(rooms);

        // cryptoSpinner.succeed('Crypto library setup, encryption ready.');

        // let encryptedRoomId = null;
        for (const roomId in rooms) {
            if (await client.crypto.isRoomEncrypted(roomId)) {
                encryptedRooId = roomId;
            }
        }

        // if (!encryptedRoomId) {
        //     encryptedRoomId = await client.createRoom({
        //         invite: [dmTarget],
        //         is_direct: true,
        //         visibility: "private",
        //         preset: "trusted_private_chat",
        //         initial_state: [
        //             {
        //                 type: "m.room.encryption",
        //                 state_key: "",
        //                 content: {algorithm: EncryptionAlgorithm.MegolmV1AesSha2}
        //             },
        //             {type: "m.room.guest_access", state_key: "", content: {guest_access: "can_join"}},
        //         ],
        //     });
        // }

        console.log('[-] ready', client.crypto.isReady);
        console.log('[-]', await client.checkOneTimeKeyCounts());

        const { one_time_keys } = await client.claimOneTimeKeys({
            [user_id]: {[device_id]: "signed_curve25519"}
        }, 10000);

        console.log({
            one_time_keys
        })

        if (!one_time_keys[user_id]) {
            console.error('One time keys are not supported for this device');
            return;
        }


        // console.log('[-] Finished setting up crypto lib');
        // // await client.begin();
        console.log('[-] Started');
        // const resp = await client.uploadDeviceOneTimeKeys(one_time_keys[user_id][device_id])

        // console.log('Thing has started', device_id, user_id, resp);
        // const spinner = ora('Loading message history from all the threads').start();
        // for (let index in rooms) {
        //     const roomId = rooms[index];
        //     const state = await client.getRoomState(roomId);

        //     await processRoomState(state, {
        //         client,
        //         roomId,
        //         Threads,
        //         Messages,
        //         People,
        //         Participant,
        //     });

        //     const isEncrypted = await client.crypto.isRoomEncrypted(roomId);
        //     const roomState = await cryptoProvider.getRoom(roomId);
        //     if (isEncrypted && !roomState) {
        //         const encryption = await client.getRoomStateEvent(roomId, 'm.room.encryption')
        //         const history_visibility = await client.getRoomStateEvent(roomId, 'm.room.history_visibility')
        //         const guest_access = await client.getRoomStateEvent(roomId, 'm.room.guest_access')

        //         await client.cryptoStore.storeRoom(roomId, {
        //             ...encryption,
        //             ...history_visibility,
        //             ...guest_access,
        //         });
        //     }

        //     spinner.stopAndPersist({
        //         text: "✔️ " + index + '/' + rooms.length + ' History sync: ' + roomId,
        //     });
        // }

        // spinner.succeed('Starting the matrix clint')
        await client.start();
        // }
    } catch (error) {
        console.error("Error:", error.message ?? error?.body ?? 'nothing defined I guess', error);
    }
}

async function processRoomState(state, {
    client,
    roomId,
    Threads,
    Messages,
    People,
    Participant,
    credential,
}) {
    for (let stateIndex in state) {
        const event = state[stateIndex];
        let test = null;
        switch (event.type) {
            case 'm.room.create':
                test = await Threads.findOne({
                    where: {thread_id: event.room_id}
                });

                if (test) {
                    continue;
                }
                await Threads.create({
                    name: roomId,
                    thread_id: roomId,
                    originated_at: dayjs(event.origin_server_ts),
                });
                break;
            case 'm.room.name':
                test = await Threads.findOne({
                    where: {thread_id: event.room_id}
                });

                if (test) {
                    continue;
                }
                await Threads.update({name: event.content.name}, {
                    where: {thread_id: roomId}
                });
                break;
            case 'm.room.member':
                let matchingPeople = await sequelize.query('select * from people where json_contains(identifiers, ?, \'$\')', {
                    replacements: [JSON.stringify(event.sender)],
                    type: QueryTypes.SELECT,
                    model: People,
                })
                const person = matchingPeople[0];

                if (matchingPeople.length === 0 && !person) {
                    await People.create({
                        name: event.content?.displayname ?? event.sender,
                        identifiers: JSON.stringify([event.sender])
                    })
                }
                matchingPeople = await sequelize.query('select * from people where json_contains(identifiers, ?, \'$\')', {
                    replacements: [JSON.stringify(event.sender)],
                    type: QueryTypes.SELECT,
                    model: People,
                })

                for (let index in matchingPeople) {
                    const person = matchingPeople[index];

                    let thread = await Threads.findOne({
                        where: {thread_id: event.room_id}
                    });
                    let participant = await sequelize.query('select * from thread_participants where person_id = ? and thread_id = ?', {
                        replacements: [JSON.stringify(person.id), JSON.stringify(thread.id)],
                        type: QueryTypes.SELECT,
                    });

                    if (participant.length === 0) {
                        await Participant.create({
                            person_id: person.id,
                            thread_id: thread.id,
                            joined_at: dayjs(event.origin_server_ts),
                        })
                        participant = await sequelize.query('select * from thread_participants where person_id = ? and thread_id = ?', {
                            replacements: [JSON.stringify(person.id), JSON.stringify(thread.id)],
                            type: QueryTypes.SELECT,
                        });
                    }

                    if (event.content?.avatar_url) {
                        let contentType = 'jpg';
                        const data = await client.downloadContent(event.content?.avatar_url);

                        if (!data.data) {
                            continue;
                        }

                        const id = person.id
                        contentType = data.contentType.split('/')[1];

                        if (!fs.existsSync('./storage/app/' + id + '.' + contentType)) {
                            const path = './storage/app/' + id + '.' + contentType;
                            fs.writeFileSync(path, data.data)

                            await People.update({
                                photo_url: path
                            }, {
                                where: {id}
                            });
                        }
                    }
                }
                break;
            case 'm.room.encryption':
                const hash = crypto.createHash('sha512');

                const key = hash.update(roomId, 'utf-8');

                const thread = key.digest('hex');
                let threadInQuestion = await Threads.findOne({
                    where: {thread_id: roomId}
                });

                const originalSettings = JSON.parse(threadInQuestion?.settings ?? '{}');

                await Threads.update({
                    settings: JSON.stringify({
                        ...originalSettings,
                        ...event.content,
                        'hash': thread
                    })
                }, {
                    where: {thread_id: roomId}
                });

                console.log('encrypted room found', await client.crypto.isRoomEncrypted(roomId));

                break;
            // this.db.set(`rooms.${key}`, ).write();
            case 'm.room.topic':
                await Threads.update({topic: event.content.topic}, {
                    where: {thread_id: roomId}
                });
                break;
            // Meta settings that dont' matter for this app
            case 'm.room.canonical_alias':
            case 'm.room.history_visibility':
            case 'm.room.guest_access':
            case 'm.room.power_levels':
            case 'm.room.avatar':
            case 'm.room.join_rules':
            case 'm.bridge':
            case 'uk.half-shot.bridge':
            case 'com.beeper.chatwoot.conversation_id':
                break;
            case 'com.beeper.backfill_status':

            case 'com.beeper.rooms.note_to_self':
            case 'com.beeper.support_chat':
                break;
            case "m.space.parent":
            case 'm.space.child':
            case 'com.beeper.feed':
            case 'org.matrix.msc2716.marker':
            // a twitter marker?

            default:
                console.log(event.type, JSON.stringify(event, null, 4));
        };
    }
}

process.on('SIGINT', function () {
    sequelize.close();

    process.exit();
})

main();
