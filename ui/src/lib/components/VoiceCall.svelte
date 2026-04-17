<script lang="ts">
	import { Phone, PhoneOff } from "lucide-svelte";
	import * as Sentry from "@sentry/svelte";

	let isConnected = false;
	let isConnecting = false;
	let room: any = null;
	let statusText = "";
	let debugLogs: string[] = [];
	let showDebug = true;

	// Portal action — телепортирует элемент в body, обходя backdrop-blur containing block
	function portal(node: HTMLElement) {
		document.body.appendChild(node);
		return { destroy() { node.remove(); } };
	}
	let participants: string[] = [];
	let micLevel = 0;
	let micAnalyser: any = null;
	let micAnimFrame: number | null = null;

	function log(msg: string) {
		const time = new Date().toLocaleTimeString();
		debugLogs = [...debugLogs, `[${time}] ${msg}`];
		console.log(`[LiveKit Debug] ${msg}`);
		// Автоскролл к последнему логу
		setTimeout(() => {
			const el = document.getElementById("debug-scroll");
			if (el) el.scrollTop = el.scrollHeight;
		}, 50);
	}

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
		log("Запрашиваю токен у бэкенда...");

		try {
			const res = await fetch("/api/livekit/token", {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({}),
			});

			if (!res.ok) throw new Error(`Token endpoint: ${res.status}`);
			const { token, url, room: roomName, identity } = await res.json();
			log(`Токен получен. Room: ${roomName}, Identity: ${identity}`);
			log(`URL: ${url}`);

			const { Room, RoomEvent, Track } = await import("livekit-client");
			log("livekit-client загружен");

			room = new Room({
				adaptiveStream: true,
				dynacast: true,
			});

			// === ВСЕ СОБЫТИЯ ===
			room.on(RoomEvent.Connected, () => {
				isConnected = true;
				isConnecting = false;
				statusText = "Подключено";
				log("✅ CONNECTED к комнате");
				updateParticipants();
			});

			room.on(RoomEvent.Disconnected, (reason: any) => {
				isConnected = false;
				isConnecting = false;
				statusText = "";
				log(`❌ DISCONNECTED: ${reason || "unknown"}`);
				stopMicMonitor();
				room = null;
				participants = [];
			});

			room.on(RoomEvent.ParticipantConnected, (participant: any) => {
				log(`👤 Участник подключился: ${participant.identity} (${participant.name || "no name"})`);
				updateParticipants();
			});

			room.on(RoomEvent.ParticipantDisconnected, (participant: any) => {
				log(`👤 Участник отключился: ${participant.identity}`);
				updateParticipants();
			});

			room.on(RoomEvent.TrackSubscribed, (track: any, publication: any, participant: any) => {
				log(`🔊 Track подписан: ${track.kind} от ${participant.identity}`);
				if (track.kind === "audio") {
					const el = track.attach();
					el.id = `audio-${participant.identity}`;
					document.body.appendChild(el);
					log(`🔈 Аудио агента воспроизводится`);
				}
			});

			room.on(RoomEvent.TrackUnsubscribed, (track: any, publication: any, participant: any) => {
				log(`🔇 Track отписан: ${track.kind} от ${participant.identity}`);
				track.detach().forEach((el: HTMLElement) => el.remove());
			});

			room.on(RoomEvent.TrackPublished, (publication: any, participant: any) => {
				log(`📡 Track опубликован: ${publication.kind} от ${participant.identity}`);
			});

			room.on(RoomEvent.DataReceived, (data: any, participant: any) => {
				const text = new TextDecoder().decode(data);
				log(`📨 Data от ${participant?.identity || "system"}: ${text.substring(0, 200)}`);
			});

			room.on(RoomEvent.ActiveSpeakersChanged, (speakers: any[]) => {
				if (speakers.length > 0) {
					log(`🗣️ Говорит: ${speakers.map((s: any) => s.identity).join(", ")}`);
				}
			});

			room.on(RoomEvent.ConnectionQualityChanged, (quality: any, participant: any) => {
				log(`📶 Качество связи ${participant.identity}: ${quality}`);
			});

			room.on(RoomEvent.RoomMetadataChanged, (metadata: string) => {
				log(`🏷️ Room metadata: ${metadata}`);
			});

			log("Подключаюсь к WebSocket...");
			await room.connect(url, token);

			log("Включаю микрофон...");
			await room.localParticipant.setMicrophoneEnabled(true);
			log("🎤 Микрофон включён");

			startMicMonitor();
			updateParticipants();

		} catch (err: any) {
			console.error("LiveKit connect error:", err);
			Sentry.captureException(err);
			log(`💥 ОШИБКА: ${err.message}`);
			statusText = err.message || "Ошибка подключения";
			isConnecting = false;
			setTimeout(() => { statusText = ""; }, 3000);
		}
	}

	function updateParticipants() {
		if (!room) return;
		const parts: string[] = [];
		parts.push(`${room.localParticipant.identity} (ты)`);
		room.remoteParticipants.forEach((p: any) => {
			parts.push(`${p.identity} (${p.name || "agent"})`);
		});
		participants = parts;
		log(`Участники в комнате: ${parts.join(", ")}`);
	}

	function startMicMonitor() {
		try {
			const audioCtx = new AudioContext();
			const micTrack = room?.localParticipant?.getTrackPublication("microphone")?.track;
			if (!micTrack) { log("⚠️ Mic track не найден для мониторинга"); return; }

			const stream = new MediaStream([micTrack.mediaStreamTrack]);
			const source = audioCtx.createMediaStreamSource(stream);
			micAnalyser = audioCtx.createAnalyser();
			micAnalyser.fftSize = 256;
			source.connect(micAnalyser);

			const data = new Uint8Array(micAnalyser.frequencyBinCount);
			function tick() {
				micAnalyser.getByteFrequencyData(data);
				micLevel = Math.max(...Array.from(data)) / 255;
				micAnimFrame = requestAnimationFrame(tick);
			}
			tick();
			log("📊 Мониторинг уровня микрофона запущен");
		} catch (e: any) {
			log(`⚠️ Mic monitor error: ${e.message}`);
		}
	}

	function stopMicMonitor() {
		if (micAnimFrame) cancelAnimationFrame(micAnimFrame);
		micAnimFrame = null;
		micLevel = 0;
	}

	async function disconnect() {
		log("Отключаюсь...");
		if (room) {
			await room.disconnect();
			document.querySelectorAll("audio").forEach((el) => el.remove());
		}
		stopMicMonitor();
		room = null;
		isConnected = false;
		statusText = "";
	}

	function clearLogs() {
		debugLogs = [];
	}
</script>

<div class="flex items-center gap-2">
	{#if statusText}
		<span class="text-xs px-2 py-1 rounded-full {isConnected ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400'} animate-pulse">
			{statusText}
		</span>
	{/if}

	{#if isConnected && micLevel > 0}
		<div class="w-16 h-3 bg-zinc-800 rounded-full overflow-hidden" title="Mic level">
			<div class="h-full bg-green-500 transition-all duration-75 rounded-full" style="width: {micLevel * 100}%"></div>
		</div>
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

	<button
		on:click={() => showDebug = !showDebug}
		class="text-xs px-2 py-1 rounded bg-zinc-800 text-zinc-400 hover:text-white"
		title="Toggle debug panel"
	>
		{showDebug ? '🔽' : '🐛'}
	</button>
</div>

{#if showDebug}
	<div use:portal class="fixed bottom-0 right-0 w-[420px] max-h-[300px] bg-zinc-950/95 border border-zinc-700 rounded-tl-xl z-50 flex flex-col text-xs font-mono">
		<div class="flex items-center justify-between px-3 py-1.5 border-b border-zinc-800">
			<span class="text-green-400 font-bold">🐛 LiveKit Debug</span>
			<div class="flex gap-2">
				{#if participants.length > 0}
					<span class="text-zinc-500">👤 {participants.length}</span>
				{/if}
				<button on:click={clearLogs} class="text-zinc-500 hover:text-white">Clear</button>
				<button on:click={() => showDebug = false} class="text-zinc-500 hover:text-white">✕</button>
			</div>
		</div>
		<div id="debug-scroll" class="flex-1 overflow-y-auto px-3 py-2 space-y-0.5 max-h-[250px]">
			{#each debugLogs as line}
				<div class="text-zinc-300 leading-tight">{line}</div>
			{/each}
			{#if debugLogs.length === 0}
				<div class="text-zinc-600 italic">Нажми 📞 чтобы начать...</div>
			{/if}
		</div>
	</div>
{/if}
