import React, { lazy, Suspense } from 'react'
import ReactDOM from 'react-dom/client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { app1Selector, app2Selector } from '@/utils'
import { StyleProvider } from '@ant-design/cssinjs'
import { TPlayerProps } from './App2/Player'
import { PageLoading } from '@/components/general'

const App1 = lazy(() => import('./App1'))
const App2 = lazy(() => import('./App2'))
const queryClient = new QueryClient({
	defaultOptions: {
		queries: {
			refetchOnWindowFocus: false,
			retry: 0,
		},
	},
})

const app1Nodes = document.querySelectorAll(app1Selector)

const mapping = [
	{
		els: app1Nodes,
		App: App1,
	},
]

mapping.forEach(({ els, App }) => {
	if (!!els) {
		els.forEach((el) => {
			ReactDOM.createRoot(el).render(
				<QueryClientProvider client={queryClient}>
					<StyleProvider hashPriority="low">
						<Suspense fallback={<PageLoading type="general" />}>
							<App />
						</Suspense>
					</StyleProvider>
				</QueryClientProvider>,
			)
		})
	}
})

// 一個畫面可能會有多個 vidstack 元素
const vidstackNodes = document.querySelectorAll(app2Selector)
vidstackNodes.forEach((vidstackNode) => {
	const dataset: TPlayerProps = vidstackNode.dataset || {}

	ReactDOM.createRoot(vidstackNode).render(
		<React.StrictMode>
			<Suspense fallback={<PageLoading type="general" />}>
				<App2 {...dataset} />
			</Suspense>
		</React.StrictMode>,
	)
})
