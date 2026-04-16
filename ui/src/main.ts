import { mount } from 'svelte'
import './app.css'
import App from './App.svelte'
import * as Sentry from '@sentry/svelte'

Sentry.init({
  dsn: import.meta.env.VITE_SENTRY_DSN,
  sendDefaultPii: true,
})

const app = mount(App, {
  target: document.getElementById('app')!,
})

export default app
