{% form_theme <?= $form_var; ?>Form 'PrestaShopBundle:Admin/TwigTemplateForm:prestashop_ui_kit.html.twig' %}

{% block <?= $form_snake_case; ?> %}
  {{ form_start(form, {attr : {class: 'form', id: <?= $form_snake_case; ?>}, action: <?= $form_action; ?> }) }}
    <div class="card">
      <h3 class="card-header">
        <i class="material-icons">settings</i> {{ '<?= $form_human_words; ?>'|trans({}, '<?= $translation_domain; ?>') }}
      </h3>
      <div class="card-block row">
        <div class="card-text">
          {{ form_widget(form) }}
        </div>
      </div>
      <div class="card-footer">
        <div class="d-flex justify-content-end">
          <button class="btn btn-primary float-right" id="save-button">
            {{ 'Save'|trans({}, 'Admin.Actions') }}
          </button>
        </div>
      </div>
    </div>
  {{ form_end(form) }}
{% endblock %}