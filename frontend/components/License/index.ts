import isPro from '../../utils/isPro'
import License from './License'
import ProLicense from './License.pro'

export default isPro() ? ProLicense : License
