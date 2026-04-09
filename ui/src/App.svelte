<script lang="ts">
	import { onMount } from "svelte";
	import { Menu } from "lucide-svelte";
	import Sidebar from "$lib/components/Sidebar.svelte";
	import MessageBubble from "$lib/components/MessageBubble.svelte";
	import InputSocket from "$lib/components/InputSocket.svelte";
	import { graphql, GET_SESSIONS, GET_EPISODES, SEND_MESSAGE, UPDATE_SESSION_PROMPT } from "$lib/api";

	// Состояние приложения
	let systemPrompt = "";
	let message = "";
	let isSidebarOpen = false;
	
	let sessions: any[] = [];
	let activeSessionId: string | null = null;
	let messages: any[] = [];
	let isTyping = false;

	onMount(async () => {
		await loadSessions();
	});

	async function loadSessions() {
		try {
			const data = await graphql(GET_SESSIONS);
			sessions = data.chatSessions;
			if (sessions.length > 0 && !activeSessionId) {
				await selectSession(sessions[0].id);
			}
		} catch (e) {
			console.error("Не удалось загрузить списки сессий:", e);
		}
	}

	async function selectSession(id: string) {
		activeSessionId = id;
		const session = sessions.find(s => s.id === id);
		if (session) {
			systemPrompt = session.systemPrompt || "";
		}
		
		try {
			const data = await graphql(GET_EPISODES, { chatSessionId: id });
			messages = data.episodes;
		} catch (e) {
			console.error("Не удалось загрузить сообщения:", e);
		}
	}

	async function saveSystemPrompt() {
		if (!activeSessionId) return;
		try {
			await graphql(UPDATE_SESSION_PROMPT, { id: activeSessionId, prompt: systemPrompt });
			const session = sessions.find(s => s.id === activeSessionId);
			if (session) session.systemPrompt = systemPrompt;
		} catch (e) {
			console.error("Не удалось сохранить промпт:", e);
		}
	}

	async function sendMessage() {
		if (!message.trim()) return;
		
		const content = message;
		message = "";
		
		// Локальное оптимистичное обновление
		messages = [...messages, { role: "user", content }];
		isTyping = true;
		
		try {
			const data = await graphql(SEND_MESSAGE, { chatSessionId: activeSessionId, content });
			// Бэкенд возвращает ответ ИИ!
			messages = [...messages, data.sendMessage];
			
			// Если не было сессии — бэкенд создал новую, обновим список
			if (!activeSessionId) {
				await loadSessions();
			}
		} catch (e) {
			console.error("Ошибка отправки:", e);
		} finally {
			isTyping = false;
		}
	}
	
	function toggleSidebar() {
		isSidebarOpen = !isSidebarOpen;
	}
</script>

<div class="flex h-screen w-full bg-slate-50 dark:bg-zinc-950 text-slate-900 dark:text-slate-100 overflow-hidden font-sans relative">
	
	<!-- Mobile Overlay -->
	{#if isSidebarOpen}
		<!-- svelte-ignore a11y_click_events_have_key_events -->
		<!-- svelte-ignore a11y_no_static_element_interactions -->
		<div 
			class="fixed inset-0 bg-black/50 z-20 md:hidden backdrop-blur-sm transition-opacity" 
			on:click={toggleSidebar}
		></div>
	{/if}

	<!-- Isolated Sidebar Component -->
	<Sidebar 
		bind:systemPrompt 
		bind:isSidebarOpen 
		{sessions} 
		{activeSessionId} 
		{selectSession} 
		{saveSystemPrompt} 
	/>

	<!-- Main Chat Area -->
	<main class="flex-1 flex flex-col bg-slate-50/50 dark:bg-zinc-950 min-w-0 relative">
		
		<!-- Header -->
		<header class="h-16 border-b border-slate-200 dark:border-zinc-800 bg-white/50 dark:bg-zinc-900/30 backdrop-blur-md shrink-0 flex items-center px-4 md:px-8 relative z-10 w-full">
			<button class="md:hidden mr-4 p-2 -ml-2 text-slate-600 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-md transition-colors" on:click={toggleSidebar}>
				<Menu class="w-5 h-5" />
			</button>
			<h2 class="font-medium truncate">
				{sessions.find(s => s.id === activeSessionId)?.title || 'Чат с ИИ'}
			</h2>
		</header>

		<!-- Message List -->
		<div class="flex-1 overflow-y-auto p-4 md:p-8 pb-32">
			<div class="max-w-3xl mx-auto space-y-6 md:space-y-8 flex flex-col">
				{#each messages as msg}
					<MessageBubble {msg} />
				{/each}

				{#if isTyping}
					<div class="flex justify-start opacity-70">
						<div class="px-5 py-3.5 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 rounded-tl-sm text-slate-500 italic text-sm shadow-sm flex items-center">
							<span class="animate-pulse">Печатает...</span>
						</div>
					</div>
				{/if}
			</div>
		</div>

		<!-- Isolated Input Component -->
		<InputSocket bind:message {sendMessage} disabled={isTyping} />
	</main>
</div>
