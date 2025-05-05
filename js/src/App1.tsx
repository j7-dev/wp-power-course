/* eslint-disable quote-props */
import { lazy, Suspense } from 'react'
import { Refine } from '@refinedev/core'
import { ErrorComponent, useNotificationProvider } from '@refinedev/antd'
import routerBindings, {
	UnsavedChangesNotifier,
	NavigateToResource,
} from '@refinedev/react-router-v6'
import { dataProvider } from './rest-data-provider'
import { dataProvider as bunnyStreamDataProvider } from './rest-data-provider/bunny-stream'
import { HashRouter, Outlet, Route, Routes } from 'react-router-dom'
import { apiUrl, kebab } from '@/utils'
import { resources } from '@/resources'
import { ConfigProvider } from 'antd'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import { PageLoading } from '@/components/general'
import { ThemedLayoutV2, ThemedSiderV2 } from '@/components/layout'

import { MediaLibraryIndicator } from '@/bunny'

const Dashboard = lazy(() => import('@/pages/admin/Dashboard'))
const CoursesList = lazy(() => import('@/pages/admin/Courses/List'))
const CoursesEdit = lazy(() => import('@/pages/admin/Courses/Edit'))
const Teachers = lazy(() => import('@/pages/admin/Teachers'))
const Students = lazy(() => import('@/pages/admin/Students'))
const Products = lazy(() => import('@/pages/admin/Products'))
const Settings = lazy(() => import('@/pages/admin/Settings'))
const Shortcodes = lazy(() => import('@/pages/admin/Shortcodes'))
const EmailsList = lazy(() => import('@/pages/admin/Emails/List'))
const EmailsEdit = lazy(() => import('@/pages/admin/Emails/Edit'))
const MediaLibraryPage = lazy(() => import('@/pages/admin/MediaLibraryPage'))

function App() {
	return (
		<HashRouter>
			<Refine
				dataProvider={{
					default: dataProvider(`${apiUrl}/${kebab}`),
					'power-email': dataProvider(`${apiUrl}/power-email`),
					powerhouse: dataProvider(`${apiUrl}/v2/powerhouse`),
					'wc-analytics': dataProvider(`${apiUrl}/wc-analytics`),
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
									Title={({ collapsed }) => <></>}
								>
									<Outlet />
									<MediaLibraryIndicator />
								</ThemedLayoutV2>
							</ConfigProvider>
						}
					>
						<Route index element={<NavigateToResource resource="courses" />} />
						<Route path="courses">
							<Route
								index
								element={
									<Suspense fallback={<PageLoading />}>
										<CoursesList />
									</Suspense>
								}
							/>
							<Route
								path="edit/:id"
								element={
									<Suspense fallback={<PageLoading />}>
										<CoursesEdit />
									</Suspense>
								}
							/>
						</Route>
						<Route
							path="teachers"
							element={
								<Suspense fallback={<PageLoading />}>
									<Teachers />
								</Suspense>
							}
						/>
						<Route
							path="students"
							element={
								<Suspense fallback={<PageLoading />}>
									<Students />
								</Suspense>
							}
						/>
						<Route
							path="products"
							element={
								<Suspense fallback={<PageLoading />}>
									<Products />
								</Suspense>
							}
						/>
						<Route
							path="shortcodes"
							element={
								<Suspense fallback={<PageLoading />}>
									<Shortcodes />
								</Suspense>
							}
						/>
						<Route
							path="settings"
							element={
								<Suspense fallback={<PageLoading />}>
									<Settings />
								</Suspense>
							}
						/>
						<Route
							path="dashboard"
							element={
								<Suspense fallback={<PageLoading />}>
									<Dashboard />
								</Suspense>
							}
						/>
						<Route path="emails">
							<Route
								index
								element={
									<Suspense fallback={<PageLoading />}>
										<EmailsList />
									</Suspense>
								}
							/>
							<Route
								path="edit/:id"
								element={
									<Suspense fallback={<PageLoading />}>
										<EmailsEdit />
									</Suspense>
								}
							/>
						</Route>
						<Route
							path="media-library"
							element={
								<Suspense fallback={<PageLoading />}>
									<MediaLibraryPage />
								</Suspense>
							}
						/>

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
