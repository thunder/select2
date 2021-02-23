/**
 * @file
 * Select2 integration.
 */
(($) => {
  Drupal.behaviors.select2_publish = {
    attach(context) {
      $('.select2-widget', context).on('select2-init', (e) => {
        if (
          typeof $(e.target).data('select2-publish-default') === 'undefined'
        ) {
          return;
        }
        const config = $(e.target).data('select2-config');

        const parentCreateTagHandler = config.createTag;
        config.createTag = (params) => {
          const term = parentCreateTagHandler(params);
          if (term) {
            term.published = $(e.target).data('select2-publish-default');
          }
          return term;
        };

        const templateHandlerWrapper = (parentHandler) => {
          return (option, item) => {
            if (parentHandler) {
              parentHandler(option, item);
            }
            if (item) {
              const published =
                option.published === true ||
                $(option.element).attr('data-published') === 'true';
              $(item).addClass(published ? 'published' : 'unpublished');
            }
            return option.text;
          };
        };

        config.templateSelection = templateHandlerWrapper(
          config.templateSelection,
        );
        config.templateResult = templateHandlerWrapper(config.templateResult);

        $(e.target).data('select2-config', config);
      });
    },
  };
})(jQuery, drupalSettings);
