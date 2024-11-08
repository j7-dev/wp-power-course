import { LeftOutlined, MoonOutlined, SunOutlined } from '@ant-design/icons'
import { Button, Dropdown, Flex, MenuProps, Select, theme } from 'antd'
import { useNavigate } from 'react-router-dom'

interface IProps {
	id?: string
	lang: string
	setLang: (lang: 'en_US' | 'zh_CN') => void
	skin: string
	setSkin: (skin: 'light' | 'dark') => void
	handleSave: () => void
	handleExport: (key: string) => void
}

const Head: React.FC<IProps> = ({
	id,
	lang,
	setLang,
	skin,
	setSkin,
	handleSave,
	handleExport,
}): JSX.Element => {
	const { token } = theme.useToken()
	const navigate = useNavigate()

	const items: MenuProps['items'] = [
		{
			key: '1',
			label: 'Export HTML',
		},
		{
			key: '2',
			label: 'Export MJML',
		},
		{
			key: '3',
			label: 'Export JSON',
		},
	]

	const handlePreview = () => {
		console.log(handleSave())
		window.open(`/easy-mail-demo/#/preview/${id}`)
	}

	return (
		<Flex
			style={{
				height: 60,
				padding: '0 15px',
				borderBottom: `1px solid ${token.colorBorder}`,
				backgroundColor: token.colorBgLayout,
			}}
			justify="space-between"
			align="center"
		>
			<Button icon={<LeftOutlined />} type="text" onClick={() => navigate('/')}>
				Back
			</Button>
			<Flex gap={10} align="center">
				<div
					onClick={() => setSkin(skin === 'dark' ? 'light' : 'dark')}
					style={{ color: token.colorText, marginRight: 10 }}
				>
					{skin === 'dark' ? <MoonOutlined /> : <SunOutlined />}
				</div>

				<Dropdown
					menu={{
						items,
						onClick: ({ key }) => {
							handleExport(key)
						},
					}}
					placement="bottomLeft"
				>
					<Button>Export</Button>
				</Dropdown>

				<Select
					value={lang}
					style={{ width: 90 }}
					onChange={(value) => setLang(value as 'en_US' | 'zh_CN')}
					options={[
						{ value: 'zh_CN', label: '中文' },
						{ value: 'en_US', label: 'English' },
					]}
				/>

				{id !== '-1' && <Button onClick={handlePreview}>Preview</Button>}

				<Button onClick={handleSave}>Save</Button>
			</Flex>
		</Flex>
	)
}
export default Head

/* <------------------------------------ **** FUNCTION COMPONENT END **** ------------------------------------ */
