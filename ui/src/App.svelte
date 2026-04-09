<script lang="ts">
	import { Send, Settings, MessageSquare, Bot, Menu, X } from "lucide-svelte";

	let systemPrompt = "Ты саркастичный ИИ-ассистент, который всегда отвечает с юмором.";
	let message = "";
	let isSidebarOpen = false;
	let messages = [
		{ role: "assistant", content: "Привет. Я OMI. Давай, удиви меня своей задачей." }
	];

	function sendMessage() {
		if (!message.trim()) return;
		messages = [...messages, { role: "user", content: message }];
		message = "";
		
		setTimeout(() => {
			messages = [...messages, { role: "assistant", content: "Окей, я подумаю над этим... (GraphQL еще не подключен)" }];
		}, 600);
	}
	
	function toggleSidebar() {
		isSidebarOpen = !isSidebarOpen;
	}
</script>

<div class="flex h-screen w-full bg-slate-50 dark:bg-zinc-950 text-slate-900 dark:text-slate-100 overflow-hidden font-sans relative">
	
	<!-- Mobile Overlay -->
	{#if isSidebarOpen}
		<div 
			class="fixed inset-0 bg-black/50 z-20 md:hidden backdrop-blur-sm transition-opacity" 
			on:click={toggleSidebar}
		></div>
	{/if}

	<!-- Sidebar -->
	<aside 
		class="fixed md:static inset-y-0 left-0 w-72 md:w-72 border-r border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex flex-col shadow-xl md:shadow-sm z-30 transition-transform duration-300 ease-in-out {isSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'}"
	>
		<div class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-zinc-800 shrink-0">
			<div class="flex items-center">
				<Bot class="w-6 h-6 mr-3 text-indigo-600 dark:text-indigo-400" />
				<h1 class="font-bold text-lg tracking-tight">OMI Messenger</h1>
			</div>
			<!-- Mobile Close Button -->
			<button class="md:hidden text-slate-500 hover:text-slate-800 dark:hover:text-slate-200" on:click={toggleSidebar}>
				<X class="w-5 h-5" />
			</button>
		</div>
		
		<div class="flex-1 overflow-y-auto p-4 space-y-2">
			<button class="w-full flex items-center text-left px-3 py-2.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 rounded-lg transition-colors font-medium text-sm">
				<MessageSquare class="w-4 h-4 mr-3 opacity-70" />
				Текущая сессия
			</button>
		</div>

		<!-- System Prompt Config -->
		<div class="p-5 border-t border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900/50 shrink-0">
			<div class="flex items-center mb-3">
				<Settings class="w-4 h-4 mr-2 text-slate-500" />
				<h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">System Prompt</h2>
			</div>
			<textarea 
				bind:value={systemPrompt} 
				placeholder="System prompt..." 
				class="w-full rounded-lg border px-3 py-2 text-xs min-h-[80px] resize-none bg-white dark:bg-zinc-950 border-slate-200 dark:border-zinc-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-shadow"
			></textarea>
			<button class="flex items-center justify-center font-medium w-full mt-3 px-3 py-2 text-xs rounded-lg bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
				Сохранить промпт
			</button>
		</div>
	</aside>

	<!-- Main Chat Area -->
	<main class="flex-1 flex flex-col bg-slate-50/50 dark:bg-zinc-950 min-w-0">
		<!-- Header -->
		<header class="h-16 border-b border-slate-200 dark:border-zinc-800 bg-white/50 dark:bg-zinc-900/30 backdrop-blur-md shrink-0 flex items-center px-4 md:px-8 relative z-10 w-full">
			<button class="md:hidden mr-4 p-2 -ml-2 text-slate-600 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-md transition-colors" on:click={toggleSidebar}>
				<Menu class="w-5 h-5" />
			</button>
			<h2 class="font-medium truncate">Чат с ИИ</h2>
		</header>

		<!-- Messages -->
		<div class="flex-1 overflow-y-auto p-4 md:p-8 pb-32">
			<div class="max-w-3xl mx-auto space-y-6 md:space-y-8">
				{#each messages as msg}
					<div class="flex {msg.role === 'user' ? 'justify-end' : 'justify-start'}">
						<div class="flex gap-3 md:gap-4 max-w-[90%] md:max-w-[80%] {msg.role === 'user' ? 'flex-row-reverse' : ''}">
							<!-- Avatar -->
							<div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center 
								{msg.role === 'user' ? 'bg-slate-200 dark:bg-zinc-800' : 'bg-indigo-600 text-white'} shadow-sm">
								{#if msg.role === 'user'}
									<span class="text-xs font-medium text-slate-600 dark:text-slate-300">U</span>
								{:else}
									<Bot class="w-4 h-4" />
								{/if}
							</div>
							
							<!-- Bubble -->
							<div class="px-4 py-3 md:px-5 md:py-3.5 rounded-2xl shadow-sm md:text-[15px] text-sm leading-relaxed
								{msg.role === 'user' 
									? 'bg-indigo-600 text-white rounded-tr-sm' 
									: 'bg-white dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 rounded-tl-sm text-slate-800 dark:text-slate-200'}">
								{msg.content}
							</div>
						</div>
					</div>
				{/each}
			</div>
		</div>

		<!-- Input Area -->
		<div class="fixed md:absolute bottom-0 left-0 w-full bg-gradient-to-t from-slate-50 dark:from-zinc-950 via-slate-50 dark:via-zinc-950/90 md:via-transparent pt-10 pb-4 md:pb-6 px-4 md:px-8 shrink-0 z-10">
			<div class="max-w-3xl mx-auto relative group">
				<textarea 
					bind:value={message}
					on:keydown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); } }}
					placeholder="Напиши сообщение..." 
					class="w-full text-sm block min-h-[50px] md:min-h-[60px] pr-12 pl-4 py-3 md:py-4 rounded-xl md:rounded-2xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm md:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all resize-none overflow-hidden"
				></textarea>
				<button 
					on:click={sendMessage}
					class="absolute right-1.5 md:right-2 bottom-1.5 md:bottom-2 w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-lg md:rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-md transition-transform active:scale-95 disabled:opacity-50 disabled:pointer-events-none"
					disabled={!message.trim()}
				>
					<Send class="w-4 h-4 ml-0.5" />
				</button>
			</div>
			<div class="max-w-3xl mx-auto text-center mt-2.5 md:mt-3 hidden md:block">
				<p class="text-[11px] text-slate-400 dark:text-zinc-500 font-medium tracking-wide">
					OMI Messenger использует GraphQL для связи с ядром.
				</p>
			</div>
		</div>
	</main>
</div>
