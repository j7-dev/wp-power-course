/* eslint-disable quote-props */
import '@/assets/scss/index.scss'
import DefaultPage from '@/pages'
import About from '@/pages/about'

import { Refine } from '@refinedev/core'

import {
  ThemedLayoutV2,
  ThemedSiderV2,
  ErrorComponent,
  useNotificationProvider,
} from '@refinedev/antd'
import '@refinedev/antd/dist/reset.css'
import routerBindings, {
  DocumentTitleHandler,
  UnsavedChangesNotifier,
} from '@refinedev/react-router-v6'
import { dataProvider } from './rest-data-provider'
import { dataProvider as bunnyStreamDataProvider } from './rest-data-provider/bunny-stream'

import { HashRouter, Outlet, Route, Routes } from 'react-router-dom'
import { apiUrl, kebab } from '@/utils'
import { resources } from '@/resources'
import Courses from '@/pages/admin/Courses'
import { ConfigProvider } from 'antd'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'

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
          syncWithLocation: false,
          warnWhenUnsavedChanges: true,
          projectId: 'power-course',
          reactQuery: {
            clientConfig: {
              defaultOptions: {
                queries: {
                  staleTime: 1000 * 60 * 15,
                  cacheTime: 1000 * 60 * 15,
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
                >
                  <Outlet />
                </ThemedLayoutV2>
              </ConfigProvider>
            }
          >
            <Route index element={<DefaultPage />} />
            <Route path="/dashboard" element={<About />} />
            <Route path="/courses" element={<Courses />} />

            <Route path="*" element={<ErrorComponent />} />
          </Route>
        </Routes>
        <UnsavedChangesNotifier />
        <DocumentTitleHandler />
        <ReactQueryDevtools initialIsOpen={false} />
      </Refine>
    </HashRouter>
  )
}

export default App
