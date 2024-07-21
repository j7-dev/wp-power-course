import { useEffect } from 'react'
import { Button, Form, Drawer, Input, Alert } from 'antd'
import { EditOutlined, GithubOutlined } from '@ant-design/icons'
import { useEditorDrawer } from '@/hooks'
import { BlockNote, useBlockNote } from 'antd-toolkit'
import { useApiUrl } from '@refinedev/core'

const { Item } = Form

/**
 * TODO 移除 BlockNote 的 checkout 插件
 * BlockNote 優化 React.lazy
 */
const DescriptionDrawer = () => {
  const apiUrl = useApiUrl()
  const form = Form.useFormInstance()
  const watchId = Form.useWatch(['id'], form)
  const { blockNoteViewProps, html } = useBlockNote({
    apiConfig: {
      apiEndpoint: `${apiUrl}/upload`,
      headers: new Headers({
        'X-WP-Nonce': window?.wpApiSettings?.nonce,
      }),
    },
  })
  const { editor } = blockNoteViewProps

  const { drawerProps, show, close, open } = useEditorDrawer()

  const handleConfirm = () => {
    form.setFieldValue(['description'], html)
    close()
  }

  useEffect(() => {
    if (watchId && open) {
      const description = form.getFieldValue(['description'])

      async function loadInitialHTML() {
        const blocks = await editor.tryParseHTMLToBlocks(description)
        editor.replaceBlocks(editor.document, blocks)
      }
      loadInitialHTML()
    }
  }, [watchId, open])

  return (
    <>
      <Button type="primary" icon={<EditOutlined />} onClick={show}>
        編輯課程重點介紹
      </Button>
      <Item name={['description']} label="課程重點介紹" hidden>
        <Input.TextArea rows={8} disabled />
      </Item>
      <Drawer
        {...drawerProps}
        extra={
          <Button type="primary" onClick={handleConfirm}>
            確認變更
          </Button>
        }
      >
        <Alert
          className="mb-4"
          message="注意事項"
          description={
            <ol className="pl-4">
              <li>
                確認變更只是確認內文有沒有變更，您還是需要儲存才會存進課程
              </li>
              <li>可以使用 WordPress shortcode</li>
              <li>
                文字與圖片對齊功能目前是無效的，持續整合中
                <a
                  target="_blank"
                  href="https://github.com/Darginec05/Yoopta-Editor/issues/205"
                  rel="noreferrer"
                >
                  <GithubOutlined className="mr-1" />
                  已通知套件作者修正
                </a>
              </li>
            </ol>
          }
          type="warning"
          showIcon
          closable
        />

        <BlockNote {...blockNoteViewProps} />
      </Drawer>
    </>
  )
}

export default DescriptionDrawer
