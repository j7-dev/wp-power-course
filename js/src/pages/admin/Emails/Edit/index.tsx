import { useState, useRef, useMemo } from 'react'
import Easymail, {
	EasymailLangType,
	EasymailSkinType,
	EasymailRefProps,
} from 'easy-mail-editor'
import mjml2html from 'mjml-browser'
import Head from './Head'
import { tpl } from './tpl'
import { Card } from 'antd'

const dataList: any[] = []
const id = '1'

const Emails = () => {
	const [lang, setLang] = useState<EasymailLangType>('en_US')
	const [skin, setSkin] = useState<EasymailSkinType>('light')

	const ref = useRef<EasymailRefProps>(null)
	const rejectRef = useRef<Promise<string>>(null)

	// const appData = useMemo(() => {
	// 	if (id == '-1') return undefined
	// 	return dataList.find((i) => i.id === Number(id))?.tree
	// }, [id])

	const handleSave = () => {
		console.log(ref.current?.getData().mjml)
	}

	const handleExport = (key: string) => {
		const fileName = dataList.find((i) => i.id === Number(id))?.name
		const { mjml, json } = (ref.current as EasymailRefProps)?.getData()
		if (key === '1') {
			console.log(mjml2html(mjml).html)
		} else if (key === '2') {
			console.log(mjml)
		} else {
			console.log(JSON.stringify(json, null, 2))
		}
	}

	const getEditorMjmlJson = () => {
		return ref.current?.getData()
	}

	return (
		<>
			<Head
				id={id}
				lang={lang}
				setLang={setLang}
				skin={skin}
				setSkin={setSkin}
				handleSave={handleSave}
				handleExport={handleExport}
			></Head>

			<Card>
				<Easymail
					lang={lang}
					width="100%"
					height="calc(100vh - 150px)"
					skin={skin}
					// colorPrimary={''}
					ref={ref}
					value={tpl}
					// tinymceLink={tinymceLink}
					onUpload={(file: File) => {
						return new Promise((resolve, reject) => {
							rejectRef.current = reject
							setTimeout(async () => {
								try {
									// const url = await fileToBase64(file)
									// resolve({ url })
								} catch (error) {
									reject('upload error')
								}
							}, 5000)
						})
					}}
					onUploadFocusChange={() => {
						rejectRef.current('error')
						rejectRef.current = null
					}}
				/>
			</Card>
		</>
	)
}
export default Emails
