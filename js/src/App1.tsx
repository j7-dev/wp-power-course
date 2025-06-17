/* eslint-disable quote-props */
import React, { lazy, Suspense } from 'react'
import { Refine } from '@refinedev/core'
import { ErrorComponent } from '@refinedev/antd'
import routerBindings, {
	UnsavedChangesNotifier,
	NavigateToResource,
} from '@refinedev/react-router'
import { HashRouter, Outlet, Route, Routes } from 'react-router'
import { resources } from '@/resources'
import { ConfigProvider } from 'antd'

import { PageLoading } from '@/components/general'
import { ThemedLayoutV2, ThemedSiderV2 } from '@/components/layout'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import { useEnv } from '@/hooks'
import {
	dataProvider,
	notificationProvider,
	useBunny,
	MediaLibraryNotification,
} from 'antd-toolkit/refine'

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
const BunnyMediaLibraryPage = lazy(
	() => import('@/pages/admin/BunnyMediaLibraryPage'),
)
const MediaLibraryPage = lazy(() => import('@/pages/admin/MediaLibraryPage'))

function App() {
	const { bunny_data_provider_result } = useBunny()
	const { KEBAB, API_URL, AXIOS_INSTANCE } = useEnv()

	return (
		<HashRouter>
			<Refine
				dataProvider={{
					default: dataProvider(`${API_URL}/v2/powerhouse`, AXIOS_INSTANCE),
					'power-email': dataProvider(`${API_URL}/power-email`, AXIOS_INSTANCE),
					'power-course': dataProvider(`${API_URL}/${KEBAB}`, AXIOS_INSTANCE),
					'wc-analytics': dataProvider(
						`${API_URL}/wc-analytics`,
						AXIOS_INSTANCE,
					),
					'wp-rest': dataProvider(`${API_URL}/wp/v2`, AXIOS_INSTANCE),
					'wc-rest': dataProvider(`${API_URL}/wc/v3`, AXIOS_INSTANCE),
					'wc-store': dataProvider(`${API_URL}/wc/store/v1`, AXIOS_INSTANCE),
					'bunny-stream': bunny_data_provider_result,
				}}
				notificationProvider={notificationProvider}
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
									<div className="pb-32">
										<Outlet />
									</div>
									<MediaLibraryNotification />
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
						<Route
							path="bunny-media-library"
							element={
								<Suspense fallback={<PageLoading />}>
									<BunnyMediaLibraryPage />
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
