import { useParams } from 'react-router-dom'
import { useMemo, useRef, useState } from 'react'
import Head from './Head'
import Easymail, {
	EasymailLangType,
	EasymailRefProps,
	EasymailSkinType,
} from 'easy-mail-editor'
import mjml2html from 'mjml-browser'

const dataList: any[] = []
const Detail = (): JSX.Element => {
	const { id } = useParams()

	const [lang, setLang] = useState<EasymailLangType>('en_US')
	const [skin, setSkin] = useState<EasymailSkinType>('light')

	const ref = useRef<EasymailRefProps>(null)

	const appData = useMemo(() => {
		if (id === '-1') return undefined
		return dataList.find((i) => i.id === Number(id))?.tree
	}, [id])

	/* <------------------------------------ **** STATE END **** ------------------------------------ */
	/* <------------------------------------ **** PARAMETER START **** ------------------------------------ */
	/************* This section will include this component parameter *************/
	/* <------------------------------------ **** PARAMETER END **** ------------------------------------ */
	/* <------------------------------------ **** FUNCTION START **** ------------------------------------ */
	/************* This section will include this component general function *************/
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

	/* <------------------------------------ **** FUNCTION END **** ------------------------------------ */
	/* <------------------------------------ **** EFFECT START **** ------------------------------------ */
	/************* This section will include this component general function *************/
	/* <------------------------------------ **** EFFECT END **** ------------------------------------ */
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

			<Easymail
				lang={lang}
				width="100vw"
				height="calc(100vh - 60px)"
				skin={skin}
				ref={ref}
				value={appData}
				// onUpload={(file: File) => {
				//   return new Promise((resolve, reject) => {
				//     rejectRef.current = reject;
				//     setTimeout(async () => {
				//       try {
				//         const url = await fileToBase64(file);
				//         resolve({ url });
				//       } catch (error) {
				//         reject("upload error");
				//       }
				//     }, 5000);
				//   });
				// }}
				onUploadFocusChange={() => {
					// rejectRef.current("error");
					// rejectRef.current = null;
				}}
			></Easymail>
		</>
	)
}
export default Detail

/* <------------------------------------ **** FUNCTION COMPONENT END **** ------------------------------------ */
