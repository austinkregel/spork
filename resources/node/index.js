import dialogflow from '@google-cloud/dialogflow';

// Instantiates a session client
const sessionClient = new dialogflow.SessionsClient();

async function detectIntent(
    query,
    contexts,
) {
    const projectId = 'kb-computers-llc--143019';
// sessionId: String representing a random number or hashed user identifier
    const sessionId = '505049310';
    const languageCode = 'en';


    // The path to identify the agent that owns the created intent.
    const sessionPath = sessionClient.projectAgentSessionPath(
        projectId,
        sessionId
    );

    // The text query request.
    const request = {
        session: sessionPath,
        queryInput: {
            text: {
                text: query,
                languageCode: languageCode,
            },
        },
    };

    if (contexts && contexts.length > 0) {
        request.queryParams = {
            contexts: contexts,
        };
    }

    const responses = await sessionClient.detectIntent(request);
    return responses[0];
}

export default detectIntent;


const run = async () => {
    let context;
    let intentResponse;

    try {
        console.log(`Sending Query: ${query}`);
        intentResponse = await detectIntent(
            query,
            context,
        );
        console.log('Detected intent');
        console.log(
            `Fulfillment Text: ${intentResponse.queryResult.fulfillmentText}`, intentResponse
        );
        // Use the context from this response for next queries
        context = intentResponse.queryResult.outputContexts;
    } catch (error) {
        console.log(error);
    }
}
