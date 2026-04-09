<script lang="ts">
	import { Settings, MessageSquare, Bot, X } from "lucide-svelte";

	export let systemPrompt: string;
	export let isSidebarOpen: boolean;
	export let sessions: any[] = [];
	export let activeSessionId: string | null = null;
	export let selectSession: (id: string) => void;
	export let saveSystemPrompt: () => void;

	function closeSidebar() {
		isSidebarOpen = false;
	}
</script>

<aside 
	class="fixed md:static inset-y-0 left-0 w-72 md:w-72 border-r border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex flex-col shadow-xl md:shadow-sm z-30 transition-transform duration-300 ease-in-out {isSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'}"
>
	<div class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-zinc-800 shrink-0">
		<div class="flex items-center">
			<Bot class="w-6 h-6 mr-3 text-indigo-600 dark:text-indigo-400" />
			<h1 class="font-bold text-lg tracking-tight">OMI Messenger</h1>
		</div>
		<!-- Mobile Close Button -->
		<button class="md:hidden text-slate-500 hover:text-slate-800 dark:hover:text-slate-200" on:click={closeSidebar}>
			<X class="w-5 h-5" />
		</button>
	</div>
	
	<div class="flex-1 overflow-y-auto p-4 space-y-2">
		{#each sessions as session (session.id)}
			<button 
				on:click={() => selectSession(session.id)}
				class="w-full flex items-center text-left px-3 py-2.5 rounded-lg transition-colors font-medium text-sm
					{session.id === activeSessionId 
						? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300' 
						: 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-zinc-800/50'}"
			>
				<MessageSquare class="w-4 h-4 mr-3 {session.id === activeSessionId ? 'opacity-70' : 'opacity-40'}" />
				<span class="truncate">{session.title || 'Новая сессия'}</span>
			</button>
		{:else}
			<div class="text-xs text-center text-slate-400 py-4">Нет активных сессий</div>
		{/each}
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
		<button 
			on:click={saveSystemPrompt}
			class="flex items-center justify-center font-medium w-full mt-3 px-3 py-2 text-xs rounded-lg bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors"
		>
			Сохранить промпт
		</button>
	</div>
</aside>
