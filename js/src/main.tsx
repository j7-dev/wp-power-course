import React from 'react'
import ReactDOM from 'react-dom/client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { app1Selector, app2Selector } from '@/utils'
import { StyleProvider } from '@ant-design/cssinjs'
import { TApp2Props } from './App2'

const App1 = React.lazy(() => import('./App1'))
const App2 = React.lazy(() => import('./App2'))
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
						<App />
					</StyleProvider>
				</QueryClientProvider>,
			)
		})
	}
})

// 一個畫面可能會有多個 vidstack 元素
const vidstackNodes = document.querySelectorAll(app2Selector)
vidstackNodes.forEach((vidstackNode) => {
	const dataset: TApp2Props = vidstackNode.dataset || {}

	ReactDOM.createRoot(vidstackNode).render(
		<React.StrictMode>
			<App2 {...dataset} />
		</React.StrictMode>,
	)
})
