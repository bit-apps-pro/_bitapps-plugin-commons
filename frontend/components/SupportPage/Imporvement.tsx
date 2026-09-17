import { __ } from '@common/helpers/i18nWrap'
import { Checkbox, theme, Typography } from 'antd'
import { type CheckboxChangeEvent } from 'antd/es/checkbox'

import pluginInfoData from './data/pluginInfoData'
import useTracking from './data/useTracking'
import useTrackingUpdate from './data/useTrackingUpdate'

const { Link, Text, Title } = Typography

interface ImprovementProps {
  pluginSlug: string
}

export default function Improvement({ pluginSlug }: ImprovementProps) {
  const { token } = theme.useToken()

  const privacyPolicyLink =
    pluginInfoData.plugins[pluginSlug as keyof typeof pluginInfoData.plugins]?.privacyPolicyLink ??
    'https://bitapps.pro/privacy-policy/'

  const { isTrackingLoading, tracking } = useTracking()
  const { isUpdatingTracking, updateTracking } = useTrackingUpdate()

  const handleCheckImprovement = (e: CheckboxChangeEvent) => {
    updateTracking(e.target.checked)
  }

  return (
    <div className="mb-12">
      <Title level={5}>{__('Improvement')} </Title>
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
          <Link href={privacyPolicyLink} rel="noopener noreferrer nofollow" target="_blank" underline>
            {__('Privacy Policy')}
          </Link>{' '}
          {__('for more information.')}
        </Text>
      </Checkbox>
    </div>
  )
}
