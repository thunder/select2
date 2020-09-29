const postcssCustomProperties = require('postcss-custom-properties');
const postcssDiscardComments = require('postcss-discard-comments');
module.exports = {
  plugins: [
    postcssCustomProperties({
      preserve: false,
      importFrom: '../../../core/themes/claro/css/base/variables.pcss.css'
    }),
    postcssDiscardComments()
  ]
}

