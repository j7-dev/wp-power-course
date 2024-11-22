import Table from '@/pages/admin/Emails/List/Table'
import AsTable from '@/pages/admin/Emails/List/AsTable'

import { List } from '@refinedev/antd'
import { Tabs } from 'antd'

const EmailsList = () => {
	return (
		<Tabs
			items={[
				{
					key: 'emails',
					label: 'Email 管理',
					children: (
						<List title="">
							<Table />
						</List>
					),
				},
				{
					key: 'email-scheduled-actions',
					label: '排程紀錄',
					children: <AsTable />,
				},
			]}
		/>
	)
}

export default EmailsList
