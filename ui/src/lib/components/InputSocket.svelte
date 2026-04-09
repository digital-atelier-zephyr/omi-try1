<script lang="ts">
	import { Send } from "lucide-svelte";

	export let message: string;
	export let sendMessage: () => void;
	export let disabled: boolean = false;
</script>

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
