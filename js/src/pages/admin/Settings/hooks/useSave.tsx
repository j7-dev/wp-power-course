import { useCustomMutation, useApiUrl } from '@refinedev/core'
import { FormInstance, message } from 'antd'

const useSave = ({ form }: { form: FormInstance }) => {
	const apiUrl = useApiUrl()
	const mutation = useCustomMutation()
	const { mutate } = mutation

	const handleSave = () => {
		message.loading({
			content: '儲存中...',
			duration: 0,
			key: 'save',
		})
		form.validateFields().then((values) => {
			mutate(
				{
					url: `${apiUrl}/options`,
					method: 'post',
					values,
				},
				{
					onSuccess: () => {
						message.success({
							content: '儲存成功',
							key: 'save',
						})
						location.reload()
					},
				},
			)
		})
	}

	return {
		handleSave,
		mutation,
	}
}

export default useSave
