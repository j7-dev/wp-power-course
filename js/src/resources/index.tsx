import {
  LineChartOutlined,
  TableOutlined,
  UserOutlined,
} from '@ant-design/icons'

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
  {
    name: 'teachers',
    list: '/teachers',
    meta: {
      label: '講師管理',
      icon: <UserOutlined />,
    },
  },
]
