/* eslint-disable quote-props */
import '@/assets/scss/index.scss'
import { Refine } from '@refinedev/core'

import {
	ThemedLayoutV2,
	ThemedSiderV2,
	ErrorComponent,
	useNotificationProvider,
} from '@refinedev/antd'
import '@refinedev/antd/dist/reset.css'
import routerBindings, {
	UnsavedChangesNotifier,
} from '@refinedev/react-router-v6'
import { dataProvider } from './rest-data-provider'
import { dataProvider as bunnyStreamDataProvider } from './rest-data-provider/bunny-stream'

import { HashRouter, Outlet, Route, Routes } from 'react-router-dom'
import { apiUrl, kebab, siteUrl } from '@/utils'
import { resources } from '@/resources'
import Dashboard from '@/pages/admin/Dashboard'
import Courses from '@/pages/admin/Courses'
import Teachers from '@/pages/admin/Teachers'
import Settings from '@/pages/admin/Settings'
import Shortcodes from '@/pages/admin/Shortcodes'
import MediaLibraryPage from '@/pages/admin/MediaLibraryPage'
import { ConfigProvider } from 'antd'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import { Logo } from '@/components/general'

function App() {
	return (
		<HashRouter>
			<Refine
				dataProvider={{
					default: dataProvider(`${apiUrl}/${kebab}`),
					'wp-rest': dataProvider(`${apiUrl}/wp/v2`),
					'wc-rest': dataProvider(`${apiUrl}/wc/v3`),
					'wc-store': dataProvider(`${apiUrl}/wc/store/v1`),
					'bunny-stream': bunnyStreamDataProvider(
						'https://video.bunnycdn.com/library',
					),
				}}
				notificationProvider={useNotificationProvider}
				routerProvider={routerBindings}
				resources={resources}
				options={{
					syncWithLocation: true,
					warnWhenUnsavedChanges: true,
					projectId: 'power-course',
					reactQuery: {
						clientConfig: {
							defaultOptions: {
								queries: {
									staleTime: 1000 * 60 * 10,
									cacheTime: 1000 * 60 * 10,
									retry: 0,
								},
							},
						},
					},
				}}
			>
				<Routes>
					<Route
						element={
							<ConfigProvider
								theme={{
									components: {
										Collapse: {
											contentPadding: '8px 8px',
										},
									},
								}}
							>
								<ThemedLayoutV2
									Sider={(props) => <ThemedSiderV2 {...props} fixed />}
									Title={({ collapsed }) => (
										<a
											href={`${siteUrl}/wp-admin/`}
											className="hover:opacity-75 transition duration-300"
										>
											<div className="flex gap-4 items-center">
												<Logo />
												{!collapsed && (
													<span className="text-gray-600 font-light">
														回網站後台
													</span>
												)}
											</div>
										</a>
									)}
								>
									<Outlet />
								</ThemedLayoutV2>
							</ConfigProvider>
						}
					>
						<Route index element={<Courses />} />
						<Route path="/courses" element={<Courses />} />
						<Route path="/teachers" element={<Teachers />} />
						<Route path="/shortcodes" element={<Shortcodes />} />
						<Route path="/settings" element={<Settings />} />
						<Route path="/dashboard" element={<Dashboard />} />
						<Route path="/media-library" element={<MediaLibraryPage />} />

						<Route path="*" element={<ErrorComponent />} />
					</Route>
				</Routes>
				<UnsavedChangesNotifier />
				<ReactQueryDevtools initialIsOpen={false} />
			</Refine>
		</HashRouter>
	)
}

export default App
