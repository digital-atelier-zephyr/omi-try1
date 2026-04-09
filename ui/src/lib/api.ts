/**
 * Ультра-легкий GraphQL клиент на голом fetch (без экосистемного мусора).
 */

export async function graphql(query: string, variables: Record<string, any> = {}) {
	try {
		const response = await fetch('/graphql', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
			},
			body: JSON.stringify({
				query,
				variables
			})
		});

		const result = await response.json();

		if (result.errors) {
			console.error("[GraphQL Errors]:", result.errors);
			throw new Error(result.errors[0].message);
		}

		return result.data;
	} catch (err) {
		console.error("[GraphQL Fetch Error]:", err);
		throw err;
	}
}

// === Мутации и Запросы ===

export const GET_SESSIONS = `
	query GetSessions {
		chatSessions(limit: 50) {
			id
			title
			systemPrompt
		}
	}
`;

export const GET_EPISODES = `
	query GetEpisodes($chatSessionId: ID!) {
		episodes(chatSessionId: $chatSessionId, limit: 100) {
			id
			role
			content
		}
	}
`;

export const UPDATE_SESSION_PROMPT = `
	mutation UpdateSystemPrompt($id: ID!, $prompt: String!) {
		updateChatSession(id: $id, systemPrompt: $prompt) {
			id
			systemPrompt
		}
	}
`;

export const SEND_MESSAGE = `
	mutation SendMessage($chatSessionId: ID!, $content: String!) {
		sendMessage(chatSessionId: $chatSessionId, content: $content) {
			id
			role
			content
		}
	}
`;
