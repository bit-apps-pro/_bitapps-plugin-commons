import { __ } from '@common/helpers/i18nWrap'
import { Col, Row, theme, Typography } from 'antd'
import { type ReactNode } from 'react'

import Changelog from '../Changelog'
import FacebookCommunityCard from '../FacebookCommunityCard'
import License from '../License.pro'
import pluginInfoData from './data/pluginInfoData'
import GiveReview from './GiveReview'
import Improvement from './Imporvement'
import RecommendedPlugins from './RecommendedPlugins'
import SupportLinks from './SupportLinks'
import config from '@config/config'

const { Paragraph, Title } = Typography

interface SupportPageProps {
  isCashBackVisible?: boolean
  isTelemetryVisible?: boolean
  logoComponent: ReactNode
  pluginSlug: string
}

export default function SupportPage({
  isCashBackVisible = true,
 isTelemetryVisible = config.IS_PRO_EXIST,
  logoComponent,
  pluginSlug
}: SupportPageProps) {
  const { token } = theme.useToken()

  const aboutPlugin = pluginInfoData.plugins[pluginSlug as keyof typeof pluginInfoData.plugins]

  return (
    <div className="p-5">
      {logoComponent}

      <Row gutter={20}>
        <Col md={15} sm={24}>
          <div className="mb-12">
            <Title level={5}>
              {__('About')} {aboutPlugin.title}
            </Title>
            <Paragraph style={{ color: token.colorTextSecondary }}>{aboutPlugin.description}</Paragraph>
          </div>

          <License pluginSlug={pluginSlug} />

          {isTelemetryVisible && <Improvement />}

          <Changelog />

          <SupportLinks pluginSlug={pluginSlug} />
        </Col>

        <Col md={9} sm={24}>
          <div className="mb-5">
            {isCashBackVisible && <GiveReview pluginSlug={pluginSlug} />}

            <FacebookCommunityCard facebookCommunityLink={pluginInfoData.facebookCommunity} />
          </div>
        </Col>
      </Row>

      <RecommendedPlugins />
    </div>
  )
}
