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
	mutation SendMessage($chatSessionId: ID, $content: String!) {
		sendMessage(chatSessionId: $chatSessionId, content: $content) {
			id
			role
			content
		}
	}
`;

export async function streamMessage(
	content: string,
	chatSessionId: string | null,
	model: string,
	onChunk: (text: string) => void,
	onDone: (data: { episodeId: string; sessionId: string; title?: string }) => void,
	onError: (err: string) => void
) {
	try {
		const response = await fetch('/api/chat/stream', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ content, chatSessionId, model })
		});

		if (!response.ok) {
			throw new Error(`\n\nHTTP \${response.status}: \${await response.text()}`);
		}

		if (!response.body) throw new Error("No readable stream in response");

		const reader = response.body.getReader();
		const decoder = new TextDecoder();
		let buffer = '';

		while (true) {
			const { done, value } = await reader.read();
			if (done) break;

			buffer += decoder.decode(value, { stream: true });
			const lines = buffer.split('\n');
			buffer = lines.pop() || ''; // Последняя неполная строка остаётся в буфере

			let currentEvent = '';
			for (const line of lines) {
				if (line.startsWith('event:')) {
					currentEvent = line.substring(6).trim();
				} else if (line.startsWith('data:')) {
					const dataStr = line.substring(5).trim();
					if (!dataStr) continue;

					try {
						const data = JSON.parse(dataStr);
						if (currentEvent === 'chunk' && data.content) {
							onChunk(data.content);
						} else if (currentEvent === 'done') {
							onDone(data);
						} else if (currentEvent === 'error') {
							onError(data.message || 'Ошибка генерации');
						}
					} catch (e) {
						// Ошибка парсинга одного чанка, игнорируем
					}
				}
			}
		}
	} catch (err: any) {
		onError(err.message || 'Сетевая ошибка');
	}
}
