<script lang="ts">
	import { Bot } from "lucide-svelte";
	export let msg: { role: string; content: string };
</script>

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
