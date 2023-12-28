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
} from "matrix-bot-sdk";
import dotenv from 'dotenv';
import Sequelize, { DataTypes, QueryTypes } from "sequelize";
import fs from 'fs';
import * as sha512 from "hash.js/lib/hash/sha/512.js";
import crypto from 'crypto';
const homeserverUrl = "https://matrix.beeper.com";
import ora from 'ora';
dotenv.config();
import Pusher from "pusher";

const storageProvider = new SimpleFsStorageProvider("/var/www/html/docker/matrix-bot/bot.json"); // or any other IStorageProvider
const cryptoProvider = new RustSdkCryptoStorageProvider("/var/www/html/docker/matrix-bot/db");
const cryptoProvider2 = new RustSdkAppserviceCryptoStorageProvider("/var/www/html/docker/matrix-bot/db");
// const auth = new MatrixAuth(homeserverUrl);
// const client = await auth.passwordLogin(process.env.MATRIX_USERNAME, process.env.MATRIX_PASSWORD);
const client = new MatrixClient(
    homeserverUrl,
    process.env.MATRIX_ACCESS_TOKEN,
    storageProvider,
    cryptoProvider
);

// In order to sync room keys, we need to ensure we can trust the devices we're getting them from.
// We can request the list of devices, and verify their keys.
//


const sequelize = new Sequelize(
    process.env.DB_DATABASE,
    process.env.DB_USERNAME,
    process.env.DB_PASSWORD,
    {
        host: process.env.DB_HOST,
        dialect: process.env.DB_CONNECTION,
        logging: false,
    }
);


// Since we're talking directly with our database, Laravel isn't getting any of the events
//   Nothing in our UI will update, and no php code will trigger.
// We need a way to hook
const pusher = new Pusher({
    appId: process.env.PUSHER_APP_ID,
    key: process.env.PUSHER_APP_KEY,
    secret: process.env.PUSHER_APP_SECRET,
    cluster: process.env.PUSHER_APP_CLUSTER,
    wsHost: process.env.PUSHER_HOST ? process.env.PUSHER_HOST : `ws-${process.env.PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: process.env.PUSHER_PORT ?? 80,
    wssPort: process.env.PUSHER_PORT ?? 443,
    useTLS: true
});

async function findOrCreateMessageEvent(Threads, Messages, People, thread, message)
{
    if (message.type !== 'm.room.message') {
        return null;
    }

    let threadInQuestion = await Threads.findOne({
        where: { thread_id: thread }
    });

    if (!threadInQuestion) {
        console.error('Thread doesn\'t exist yet', thread);

        process.exit(1);
    }

    const messageToCreate = await Messages.findOne({
        where: { event_id: message.event_id, }
    });


    if (!messageToCreate) {
        const thread_id = threadInQuestion.id

        const sender = await findPersonByIdentifier(People, message);
        console.log('no messages found for event', message.event_id,  'creating message',{
            ...(message?.content?.info?.thumbnail_url !== undefined ? {
                thumbnail_url: message.content.info.thumbnail_url,
            } : {}),
            from_person: sender?.id,
            thread_id,
            type: message.type,
            originated_at: new Date(message.origin_server_ts),
            message: message?.content?.body,
            event_id: message.event_id,
            html_message: message?.content?.format_body,
        } )

        await Messages.create({
            ...(message?.content?.info?.thumbnail_url !== undefined ? {
                thumbnail_url: message.content.info.thumbnail_url,
            } : {}),
            from_person: sender?.id,
            thread_id,
            type: message.type,
            originated_at: new Date(message.origin_server_ts),
            message: message?.content?.body,
            event_id: message.event_id,
            html_message: message?.content?.format_body,
        });

        const updatedTime = { origin_server_ts: new Date() };

        pusher.trigger('user.1', 'eloquent.updated: App\\Models\\Thread', {
            ...threadInQuestion,
            ...updatedTime,
        })
        await Threads.update(updatedTime, {
            where: { thread_id: thread }
        });
    }
}

async function findPersonByIdentifier(People, message)
{
    const identifier = message.sender;

    let peoples = await sequelize.query('select * from people where JSON_CONTAINS(identifiers, :identifier, \'$\')', {
        type: QueryTypes.SELECT,
        replacements: {
            identifier: JSON.stringify(identifier)
        },
    })

    if (!peoples || peoples.length === 0) {
        if (message.type !== 'm.room.member') {
            console.log('m.room.member', message);
            return null;
        }

        await People.create({
            name: message.content.displayname,
            identifiers: JSON.stringify([identifier])
        })

        const data = await client.downloadContent(message.content.avatar_url);

        console.log(data);
        process.exit();

        peoples = await sequelize.query('select * from people where JSON_CONTAINS(identifiers, :identifier, \'$\')', {
            type: QueryTypes.SELECT,
            replacements: {
                identifier: JSON.stringify(identifier)
            },
        });
    }


    return peoples[0] ?? null;
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
        from_person: {
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
        thumbnail_url: {
            type: DataTypes.STRING(2048),
        },
        originated_at: {
            type: DataTypes.DATE,
        },
        message: {
            type: DataTypes.TEXT
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
    console.log('[-] Sequilize models defined...');

    return {
        Threads,
        Messages,
        People,
        Participant,
    }
}

async function main() {
    try {
        const { user_id, device_id } = await client.getWhoAmI();
        console.log('[-]   #############################');
        console.log('[-]   Authenticated as', user_id, device_id);
        console.log('[-]   #############################');

        if (!await storageProvider.isUserRegistered(user_id)) {
            storageProvider.addRegisteredUser(user_id);
        }

        const {
            Threads,
            Messages,
            People,
            Participant,
        } = await setupSequlize();

        // const client = await auth.passwordLogin(userId, password, "Programmatic Access");
        console.log('[-] Autojoin rooms mixin...');
        AutojoinRoomsMixin.setupOnClient(client);
        AutojoinUpgradedRoomsMixin.setupOnClient(client);
        console.log('[-] Autojoin rooms mixin established..');

        client.on('room.join', async (event) => {
            await client.crypto.onRoomJoin(event.room_id);
        })

        client.on('room.encrypted_event', (roomId, event) => {
          // handle `m.room.encrypted` event that was received from the server
            console.log('Room Ecnrypted eevent', roomId, event)
      })


        client.on("room.message", async (roomId, messageEvent) => {
            // await client.upload
            console.log(roomId, 'received a new message from', messageEvent?.sender)
            await findOrCreateMessageEvent(Threads, Messages, People, roomId, messageEvent);
        });

        console.log('[-] Preparing crypto lib');
        const cryptoSpinner = ora('Syncing joined rooms, and preparing encryption').start();
        /** @var IStorageProvider userStorage **/
        const userStorage = await client.storageProvider.storageForUser(user_id)

        const rooms = await client.getJoinedRooms()

        await client.crypto.prepare(rooms);
        cryptoSpinner.succeed('Crypto library setup, encryption ready.');

        console.log('[-] ready', client.crypto.isReady);

        console.log('[-]',await client.checkOneTimeKeyCounts());

        const { one_time_keys } = await client.claimOneTimeKeys({
            [user_id]: {[device_id]: "signed_curve25519"}
        }, 10000);

        if (!one_time_keys[user_id]) {
            console.error('One time keys are not supported for this device');
            return;
        }

        console.log('[-]', one_time_keys);

        console.log('[-] Finished setting up crypto lib');
        // await client.begin();
        console.log('[-] Started');
        const resp = await client.uploadDeviceOneTimeKeys(one_time_keys[user_id][device_id])

        console.log('Thing has started', device_id, user_id, resp);
        const spinner = ora('Loading message history from all the threads').start();

        for (let index in rooms) {
            const roomId = rooms[index];

             // spinner.spinner = 'dots';
             // spinner.text  = index+'/'+rooms.length+' history syncing: ' + roomId
             // spinner.start()

             const isEncrypted = await client.crypto.isRoomEncrypted(roomId);
             if (isEncrypted) {
                 await client.crypto.onRoomJoin(roomId);
             }
             // const state = await client.getRoomState(roomId);

             // for (let stateIndex in state) {
             //     const event = state[stateIndex];
             //     let test = null;
             //     switch (event.type) {
             //         case 'm.room.create':
             //             test = await Threads.findOne({
             //                 where: { thread_id: event.room_id }
             //             });
             //
             //             if (test) {
             //                 continue;
             //             }
             //             await Threads.create({
             //                 name: roomId,
             //                 thread_id: roomId,
             //                 origin_server_ts: new Date(event.origin_server_ts),
             //             });
             //             break;
             //         case 'm.room.name':
             //             test = await Threads.findOne({
             //                 where: { thread_id: event.room_id }
             //             });
             //
             //             if (test) {
             //                 continue;
             //             }
             //             await Threads.update({ name: event.content.name }, {
             //                 where: { thread_id: roomId }
             //             });
             //             break;
             //         case 'm.room.member':
             //             let matchingPeople = await sequelize.query('select * from people where json_contains(identifiers, ?, \'$\')', {
             //                 replacements: [JSON.stringify(event.sender)],
             //                 type: QueryTypes.SELECT,
             //                 model: People,
             //             })
             //
             //             if (matchingPeople.length === 0) {
             //                 await People.create({
             //                     name: event.content?.displayname ?? event.sender,
             //                     identifiers: JSON.stringify([event.sender])
             //                 })
             //             }
             //             matchingPeople = await sequelize.query('select * from people where json_contains(identifiers, ?, \'$\')', {
             //                 replacements: [JSON.stringify(event.sender)],
             //                 type: QueryTypes.SELECT,
             //                 model: People,
             //             })
             //
             //             for (let index in matchingPeople) {
             //                 const person = matchingPeople[index];
             //
             //                 let thread = await Threads.findOne({
             //                     where: { thread_id: event.room_id }
             //                 });
             //                 let participant = await sequelize.query('select * from thread_participants where person_id = ? and thread_id = ?', {
             //                     replacements: [JSON.stringify(person.id), JSON.stringify(thread.id)],
             //                     type: QueryTypes.SELECT,
             //                 });
             //
             //                 if (participant.length === 0) {
             //                     await Participant.create({
             //                         person_id: person.id,
             //                         thread_id: thread.id,
             //                         joined_at: new Date(event.origin_server_ts),
             //                     })
             //                     participant = await sequelize.query('select * from thread_participants where person_id = ? and thread_id = ?', {
             //                         replacements: [JSON.stringify(person.id), JSON.stringify(thread.id)],
             //                         type: QueryTypes.SELECT,
             //                     });
             //                 }
             //
             //                 if (event.content?.avatar_url) {
             //                     let contentType = 'jpg';
             //                     const data = await client.downloadContent(event.content?.avatar_url);
             //
             //                     if (!data.data) {
             //                         continue;
             //                     }
             //
             //                     const id = person.id
             //                     contentType = data.contentType.split('/')[1];
             //
             //                     if (!fs.existsSync('/var/www/html/storage/app/' + id + '.' + contentType)) {
             //                         const path = '/var/www/html/storage/app/' + id + '.' + contentType;
             //                         fs.writeFileSync(path, data.data)
             //
             //                         await People.update({
             //                             photo_url: path
             //                         }, {
             //                             where: { id }
             //                         });
             //                     }
             //                 }
             //             }
             //             break;
             //         case 'm.room.encryption':
             //             const hash = crypto.createHash('sha512');
             //
             //             const key = hash.update(roomId, 'utf-8');
             //
             //             const thread = key.digest('hex');
             //             let threadInQuestion = await Threads.findOne({
             //                 where: { thread_id: roomId }
             //             });
             //
             //             const originalSettings = JSON.parse(threadInQuestion?.settings ?? '{}');
             //
             //             await Threads.update({
             //                 settings: JSON.stringify({
             //                     ...originalSettings,
             //                     ...event.content,
             //                     'hash': thread
             //                 })
             //             }, {
             //                 where: { thread_id: roomId }
             //             });
             //
             //             console.log('encrypted room found', await client.crypto.isRoomEncrypted(roomId));
             //
             //             break;
             //         // this.db.set(`rooms.${key}`, ).write();
             //         case 'm.room.topic':
             //             await Threads.update({ topic: event.content.topic }, {
             //                 where: { thread_id: roomId }
             //             });
             //             break;
             //         // Meta settings that dont' matter for this app
             //         case 'm.room.canonical_alias':
             //         case 'm.room.history_visibility':
             //         case 'm.room.guest_access':
             //         case 'm.room.power_levels':
             //         case 'm.room.avatar':
             //         case 'm.room.join_rules':
             //         case 'm.bridge':
             //         case 'uk.half-shot.bridge':
             //         case 'com.beeper.chatwoot.conversation_id':
             //             break;
             //         case 'com.beeper.backfill_status':
             //
             //         case 'com.beeper.rooms.note_to_self':
             //         case 'com.beeper.support_chat':
             //             break;
             //         case "m.space.parent":
             //         case 'm.space.child':
             //         case 'com.beeper.feed':
             //         case 'org.matrix.msc2716.marker':
             //         // a twitter marker?
             //
             //         default:
             //             console.log(event.type, JSON.stringify(event, null, 4));
             //     };
             // }

             spinner.stopAndPersist({
                 text: "✔️ " + index+'/'+rooms.length+' History sync: ' + roomId,
             });
        }

        spinner.succeed('Starting the matrix clint')
        await client.start()

    } catch (error) {
        console.error("Error:", error.message, error);
    }
}

process.on('SIGINT', function () {
    sequelize.close();

    process.exit();
})

main();