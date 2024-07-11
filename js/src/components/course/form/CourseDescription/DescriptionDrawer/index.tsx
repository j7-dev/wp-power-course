import { useEffect } from 'react'
import { Button, Form, Drawer, Input, Alert } from 'antd'
import { EditOutlined, GithubOutlined } from '@ant-design/icons'
import { useEditorDrawer } from '@/hooks'
import { Editor, useEditor } from 'antd-toolkit'
import { YooptaEditorProps } from 'antd-toolkit/dist/components/Editor/types'
import { useApiUrl } from '@refinedev/core'
import { SlateElement } from '@yoopta/editor'

const { Item } = Form

/**
 * TODO 取消圖片的預設 align
 * https://github.com/Darginec05/Yoopta-Editor/issues/205
 * BUG 還沒解決時，先用 fixedYooptaEditorProps
 */

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

  const fixedYooptaEditorProps = fixedYooptaEditor(yooptaEditorProps)

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

        <Editor {...fixedYooptaEditorProps} />
      </Drawer>
    </>
  )
}

export default DescriptionDrawer

function fixedYooptaEditor(props: YooptaEditorProps): YooptaEditorProps {
  const { plugins } = props

  const Embed = plugins.find((obj) => obj?.plugin?.type === 'Embed')
  if (!!Embed) {
    /**
     * 解決 Embed 沒有 iframe close tag 的問題
     * @see https://github.com/Darginec05/Yoopta-Editor/issues/207
     */

    Embed.plugin.parsers.html.serialize = (
      element: SlateElement,
      text: string,
    ) => {
      const URL_BUILDERS = {
        youtube: (id: string) => `https://www.youtube.com/embed/${id}`,
        vimeo: (id: string) => `https://player.vimeo.com/embed/${id}`,
        dailymotion: (id: string) =>
          `https://www.dailymotion.com/embed/embed/${id}`,
        figma: (id: string) =>
          `https://www.figma.com/embed?embed_host=share&url=${id}`,
      }

      let url = element.props.provider.url

      if (URL_BUILDERS[element.props.provider.type]) {
        url = URL_BUILDERS[element.props.provider.type](
          element.props.provider.id,
        )
      }

      return `<div style="display: flex; width: 100%; justify-content: center">
		<iframe src="${url}" width="${element.props.sizes.width}" height="${element.props.sizes.height}"></iframe></div>`
    }
  }

  const Image = plugins.find((obj) => obj?.plugin?.type === 'Image')
  if (!!Image) {
    /**
     * 解決 Image 對齊的問題
     * @param element
     * @param text
     */

    Image.plugin.parsers.html.serialize = (
      element: SlateElement,
      text: string,
    ) => {
      return `<div style="display: flex; width: 100%; justify-content: start">
        <img src="${element.props.src}" alt="${element.props.alt}" width="${element.props.sizes.width}" height="${element.props.sizes.height}"  />
        </div>`
    }
  }

  return props
}
