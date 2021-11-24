{% form_theme <?= $entity_var; ?>Form 'PrestaShopBundle:Admin/TwigTemplateForm:prestashop_ui_kit.html.twig' %}

{% block <?= $entity_snake; ?>_form %}
  {{ form_start(<?= $entity_var; ?>Form) }}
    <div class="card">
      <h3 class="card-header">
        <i class="material-icons">mail_outline</i>
        {{ '<?= $entity_human_words; ?>'|trans({}, 'Admin.Shopparameters.Feature') }}
      </h3>
      <div class="card-block row">
        <div class="card-text">
          {{ form_widget(<?= $entity_var; ?>Form) }}
        </div>
      </div>
      <div class="card-footer">
        <a href="{{ path('admin_<?= $entity_snake; ?>_index') }}" class="btn btn-outline-secondary">
          {{ 'Cancel'|trans({}, 'Admin.Actions') }}
        </a>
        <button class="btn btn-primary float-right" id="save-button">
          {{ 'Save'|trans({}, 'Admin.Actions') }}
        </button>
      </div>
    </div>
  {{ form_end(<?= $entity_var; ?>Form) }}
{% endblock %}