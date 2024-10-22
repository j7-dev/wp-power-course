import {
	LineChartOutlined,
	TableOutlined,
	UserOutlined,
	CodeOutlined,
	SettingOutlined,
	ProductOutlined,
} from '@ant-design/icons'
import { FaPhotoVideo } from 'react-icons/fa'
import { PiStudent } from 'react-icons/pi'

export const resources = [
	{
		name: 'courses',
		list: '/courses',
		edit: '/courses/edit/:id',
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
		name: 'students',
		list: '/students',
		meta: {
			label: '學員管理',
			icon: <PiStudent />,
		},
	},
	{
		name: 'products',
		list: '/products',
		meta: {
			label: '課程權限綁定', // 商品管理
			icon: <ProductOutlined />,
		},
	},
	{
		name: 'media-library',
		list: '/media-library',
		meta: {
			label: 'Bunny 媒體庫',
			icon: <FaPhotoVideo />,
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
	{
		name: 'dashboard',
		list: '/dashboard',
		meta: {
			label: '分析',
			icon: <LineChartOutlined />,
		},
	},
]
