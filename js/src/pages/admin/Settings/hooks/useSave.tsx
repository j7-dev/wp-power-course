import { useCustomMutation, useApiUrl } from '@refinedev/core'
import { FormInstance } from 'antd'

const useSave = ({ form }: { form: FormInstance }) => {
	const apiUrl = useApiUrl()
	const mutation = useCustomMutation()
	const { mutate } = mutation

	const handleSave = () => {
		form.validateFields().then((values) => {
			mutate({
				url: `${apiUrl}/options`,
				method: 'post',
				values,
			})
		})
	}

	return {
		handleSave,
		mutation,
	}
}

export default useSave
