import Table from '@/pages/admin/Emails/List/Table'
import AsTable from '@/pages/admin/Emails/List/AsTable'
import { List } from '@refinedev/antd'
import { Tabs, Button } from 'antd'
import { siteUrl } from '@/utils'

const EmailsList = () => {
	return (
		<Tabs
			tabBarExtraContent={
				<Button
					href={`${siteUrl}/wp-admin/admin.php?page=wc-status&tab=action-scheduler&s=power_email_send_`}
					target="_blank"
				>
					查看 Woocommerce 排程紀錄
				</Button>
			}
			items={[
				{
					key: 'emails',
					label: 'Email 模板管理',
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
