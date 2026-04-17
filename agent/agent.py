import os

from livekit import agents
from livekit.agents import AgentServer, AgentSession, Agent
from livekit.plugins import openai, silero


class OmiAssistant(Agent):
    def __init__(self):
        super().__init__(
            instructions=(
                "Ты — OMI, голосовой AI ассистент. "
                "Отвечай кратко и по делу. Говори по-русски. "
                "Не используй эмодзи, звёздочки или форматирование — ты говоришь голосом."
            )
        )


server = AgentServer()


@server.rtc_session(agent_name="omi-agent")
async def omi_agent(ctx: agents.JobContext):
    session = AgentSession(
        stt=openai.STT(),
        llm=openai.LLM(
            model="deepseek-chat",
            base_url="https://api.deepseek.com/v1",
            api_key=os.environ.get("DEEPSEEK_API_KEY"),
        ),
        tts=openai.TTS(voice="nova"),
        vad=silero.VAD.load(),
    )

    await session.start(
        room=ctx.room,
        agent=OmiAssistant(),
    )

    await session.generate_reply(
        instructions="Поприветствуй пользователя кратко."
    )


if __name__ == "__main__":
    agents.cli.run_app(server)
