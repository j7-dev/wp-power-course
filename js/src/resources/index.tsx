import {
	LineChartOutlined,
	TableOutlined,
	UserOutlined,
	CodeOutlined,
	SettingOutlined,
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
	{
		name: 'shortcodes',
		list: '/shortcodes',
		meta: {
			label: '短代碼',
			icon: <CodeOutlined />,
		},
	},
	{
		name: 'settings',
		list: '/settings',
		meta: {
			label: '設定',
			icon: <SettingOutlined />,
		},
	},
]
