import React from 'react'
import { Result } from 'antd'

const index = () => {
  return (
    <Result
      icon={
        <>
          <iframe
            src="https://giphy.com/embed/JIX9t2j0ZTN9S"
            width="480"
            height="480"
            frameBorder="0"
            className="giphy-embed"
            allowFullScreen
          ></iframe>
        </>
      }
      title="🚧...施工中...🚧"
    />
  )
}

export default index
