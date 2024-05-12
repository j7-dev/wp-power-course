import { LineChartOutlined, TableOutlined } from '@ant-design/icons'

export const resources = [
  {
    name: 'dashboard',
    list: '/dashboard',
    meta: {
      label: '分析',
      icon: <LineChartOutlined />,
    },
  },
  {
    name: 'courses',
    list: '/courses',
    meta: {
      label: '課程列表',
      icon: <TableOutlined />,
    },
  },
]
