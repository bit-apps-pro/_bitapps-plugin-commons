import { __ } from '@wordpress/i18n'
import { Checkbox, theme, Typography } from 'antd'
import { type CheckboxChangeEvent } from 'antd/es/checkbox'

import useTracking from './data/useTracking'
import useTrackingUpdate from './data/useTrackingUpdate'

const { Link, Text, Title } = Typography

export default function Improvement() {
  const { token } = theme.useToken()

  const { isTrackingLoading, tracking } = useTracking()
  const { isUpdatingTracking, updateTracking } = useTrackingUpdate()

  const handleCheckImprovement = (e: CheckboxChangeEvent) => {
    updateTracking(e.target.checked)
  }

  return (
    <div className="mb-12">
      <Title level={5}>{__('Improvement', 'bit-pi')} </Title>
      <Checkbox
        checked={tracking?.allowTracking}
        disabled={isTrackingLoading || isUpdatingTracking}
        onChange={handleCheckImprovement}
        style={{ color: token.colorTextSecondary }}
      >
        <Text>
          {__(
            "Allow the collection of diagnostic data and error reports to enhance the application's performance. Please review our"
          )}{' '}
          <Link
            href="https://bitapps.pro/privacy-policy/"
            rel="noopener noreferrer nofollow"
            target="_black"
            underline
          >
            {__('Privacy Policy', 'bit-pi')}
          </Link>{' '}
          {__('for more information.', 'bit-pi')}
        </Text>
      </Checkbox>
    </div>
  )
}
