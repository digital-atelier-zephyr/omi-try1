<script lang="ts">
	import { Phone, PhoneOff } from "lucide-svelte";

	let isConnected = false;
	let isConnecting = false;
	let room: any = null;
	let statusText = "";

	async function toggleCall() {
		if (isConnected) {
			await disconnect();
		} else {
			await connect();
		}
	}

	async function connect() {
		isConnecting = true;
		statusText = "Подключение...";

		try {
			// 1. Получаем токен от бэкенда
			const res = await fetch("/api/livekit/token", {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({}),
			});

			if (!res.ok) throw new Error("Не удалось получить токен");
			const { token, url } = await res.json();

			// 2. Динамический импорт livekit-client (не грузим пока не нажали)
			const { Room, RoomEvent } = await import("livekit-client");

			// 3. Подключаемся к комнате
			room = new Room();

			room.on(RoomEvent.Connected, () => {
				isConnected = true;
				isConnecting = false;
				statusText = "Подключено";
			});

			room.on(RoomEvent.Disconnected, () => {
				isConnected = false;
				isConnecting = false;
				statusText = "";
				room = null;
			});

			room.on(RoomEvent.TrackSubscribed, (track: any) => {
				if (track.kind === "audio") {
					const el = track.attach();
					document.body.appendChild(el);
				}
			});

			await room.connect(url, token);

			// 4. Публикуем микрофон
			await room.localParticipant.setMicrophoneEnabled(true);
		} catch (err: any) {
			console.error("LiveKit connect error:", err);
			statusText = err.message || "Ошибка подключения";
			isConnecting = false;
			setTimeout(() => {
				statusText = "";
			}, 3000);
		}
	}

	async function disconnect() {
		if (room) {
			await room.disconnect();
			// Удаляем все аудио-элементы агента
			document
				.querySelectorAll("audio")
				.forEach((el) => el.remove());
		}
		room = null;
		isConnected = false;
		statusText = "";
	}
</script>

<div class="flex items-center gap-2">
	{#if statusText}
		<span
			class="text-xs px-2 py-1 rounded-full {isConnected
				? 'bg-green-500/20 text-green-400'
				: 'bg-yellow-500/20 text-yellow-400'} animate-pulse"
		>
			{statusText}
		</span>
	{/if}

	<button
		on:click={toggleCall}
		disabled={isConnecting}
		class="p-2 rounded-lg transition-all duration-200 {isConnected
			? 'bg-red-500 text-white hover:bg-red-600 shadow-lg shadow-red-500/30'
			: 'text-slate-500 hover:bg-slate-100 dark:hover:bg-zinc-800 hover:text-indigo-500'} disabled:opacity-50"
		title={isConnected ? "Завершить звонок" : "Голосовой вызов"}
	>
		{#if isConnected}
			<PhoneOff class="w-5 h-5" />
		{:else}
			<Phone class="w-5 h-5" />
		{/if}
	</button>
</div>
