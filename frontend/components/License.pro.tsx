import request from '@common/helpers/request'
import config from '@config/config'
import If from '@utilities/If'
import { __ } from '@wordpress/i18n'
import { Badge, Button, Space, Tag } from 'antd'
import Title from 'antd/es/typography/Title'
import { useEffect, useRef } from 'react'
import { LuBadgeCheck, LuCircleX, LuCrown } from 'react-icons/lu'
import { useSearchParam } from 'react-use'

import LicenseActivationNotice from './LicenseActivationNotice.pro'
import CheckNewUpdate from './SupportPage/CheckNewUpdate'
import pluginInfo from './SupportPage/data/pluginInfoData'

const SUBS_URL =
  `h_t_tps_:/_/subscription_.bitapps_.pro/wp/activateLicense/?slug=${config.PRO_SLUG}&redirect=${encodeURIComponent(window.location.href)}`.replaceAll(
    '_',
    ''
  )

const SITE_BASE_URL = config.SITE_BASE_URL?.endsWith('/')
  ? config.SITE_BASE_URL.slice(0, -1)
  : config.SITE_BASE_URL

const handleDeactivateLicense = async () => {
  if (SITE_BASE_URL !== config.SITE_URL) return

  await request('pro_license/deactivate')

  window.location.reload()
}

const getCurrentBuildCodeName = (): string | undefined => {
  const scripts = [...document.scripts]
  for (const sc of scripts) {
    if (sc.src.includes('bit-pi') && sc.src.includes('main')) {
      return sc?.src
        ?.split('/')
        ?.at(-1)
        ?.replace('main', '')
        ?.replace('.js', '')
        ?.split('-')
        .filter(Boolean)
        .join('-')
    }
  }
}

export default function License({ pluginSlug }: { pluginSlug: string }) {
  const aboutPlugin = pluginInfo.plugins[pluginSlug as keyof typeof pluginInfo.plugins]
  const licenseKey = useRef(useSearchParam('licenseKey'))
  const currentBuildCodeName = getCurrentBuildCodeName()

  const {
    FREE_VERSION: freeVersion,
    IS_PRO: isLicenseConnected,
    IS_PRO_EXIST: hasProPlugin,
    PRO_VERSION: proVersion
  } = config

  const handleActivateLicense = () => {
    if (isLicenseConnected || SITE_BASE_URL !== config.SITE_URL) return

    const openedWindow = window.open(SUBS_URL, 'newWindow', 'width=800,height=600')

    const windowCloseChecker = setInterval(() => {
      if (!openedWindow?.closed) return

      clearInterval(windowCloseChecker)

      window.location.reload()
    }, 1000)
  }

  const activateLicense = async () => {
    await request('pro_license/activate', { licenseKey: licenseKey.current })

    //remote validity check data from local storage
    localStorage.removeItem(btoa(`${config.PRO_SLUG}-check-validity`))

    window.close()
  }

  useEffect(() => {
    if (!licenseKey.current) return

    const url = new URL(window.location.href)
    url.searchParams.delete('licenseKey')
    window.history.replaceState({}, '', url)

    activateLicense()
  }, [])

  return (
    <div className="mb-12">
      <Title level={5}>{__('License & Activation', 'bit-pi')}</Title>

      <If conditions={currentBuildCodeName === '.tsx'}>
        <Tag className="mb-2 font-bold" color="blue">
          {__('Dev Version On', 'bit-pi')}
        </Tag>
      </If>

      <div className="mb-2">
        {__('Version', 'bit-pi')}: {freeVersion}
      </div>
      <If conditions={!hasProPlugin}>
        <div className="mb-2">
          <Space className="mb-2">
            <div>
              {__('Pro Version', 'bit-pi')}: <b>{__('Not Activated', 'bit-pi')}</b>
            </div>
            <Badge dot>
              <Button
                href={aboutPlugin.buyLink}
                icon={<LuCrown />}
                rel="noopener noreferrer nofollow"
                target="_blank"
                type="primary"
              >
                {__('Buy Pro Version', 'bit-pi')}
              </Button>
            </Badge>
          </Space>

          <CheckNewUpdate />
        </div>
      </If>

      <If conditions={hasProPlugin}>
        <div className="mb-2">
          <div className="mb-2">
            {__('Pro Version', 'bit-pi')}: {proVersion}
          </div>

          <CheckNewUpdate />

          <div>
            {isLicenseConnected ? (
              <>
                <Button
                  className="mb-2"
                  danger
                  disabled={SITE_BASE_URL !== config.SITE_URL}
                  icon={<LuCircleX />}
                  onClick={handleDeactivateLicense}
                  size="large"
                  type="primary"
                >
                  {__('Deactivate License', 'bit-pi')}
                </Button>

                <LicenseActivationNotice />
              </>
            ) : (
              <Button
                disabled={SITE_BASE_URL !== config.SITE_URL}
                icon={<LuBadgeCheck />}
                onClick={handleActivateLicense}
                size="large"
                type="primary"
              >
                {__('Activate License', 'bit-pi')}
              </Button>
            )}
          </div>
        </div>
      </If>
    </div>
  )
}
