import { useEffect } from 'react'
import { Button, Form, Drawer, Input, Alert } from 'antd'
import { EditOutlined, GithubOutlined } from '@ant-design/icons'
import { useEditorDrawer } from '@/hooks'
import { Editor, useEditor } from 'antd-toolkit'
import { useApiUrl } from '@refinedev/core'

const { Item } = Form

const DescriptionDrawer = () => {
  const apiUrl = useApiUrl()
  const form = Form.useFormInstance()
  const watchId = Form.useWatch(['id'], form)
  const { yooptaEditorProps, getHtmlFromBlocks, setBlocksFromHtml } = useEditor(
    {
      apiEndpoint: `${apiUrl}/upload`,
      headers: {
        'X-WP-Nonce': window?.wpApiSettings?.nonce,
      } as any,
    },
  )

  const { drawerProps, show, close, open } = useEditorDrawer()

  const handleConfirm = () => {
    const rawHtml = getHtmlFromBlocks()

    // 將 <body 與 </body 換成 <div 與 </div
    const filteredHtml = rawHtml
      .replace(/<body/g, '<div')
      .replace(/<\/body>/g, '</div>')

    form.setFieldValue(['description'], filteredHtml)

    close()
  }

  useEffect(() => {
    if (watchId && open) {
      const description = form.getFieldValue(['description'])
      setBlocksFromHtml(description)
    }
  }, [watchId, open])

  return (
    <>
      <Button type="primary" icon={<EditOutlined />} onClick={show}>
        編輯課程重點介紹
      </Button>
      <Item name={['description']} label="課程重點介紹">
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
                文字的上色功能、縮排效果、圖片的排列方向目前是無效的，
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

        <Editor {...yooptaEditorProps} />
      </Drawer>
    </>
  )
}

export default DescriptionDrawer
