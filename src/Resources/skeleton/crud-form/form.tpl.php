{% form_theme <?php echo $entityVar; ?>Form 'PrestaShopBundle:Admin/TwigTemplateForm:prestashop_ui_kit.html.twig' %}

{% block <?php echo $entitySnake; ?>_form %}
  {{ form_start(<?php echo $entityVar; ?>Form) }}
    <div class="card">
      <h3 class="card-header">
        <i class="material-icons">mail_outline</i>
        {{ '<?php echo $entityHumanWords; ?>'|trans({}, 'Admin.Shopparameters.Feature') }}
      </h3>
      <div class="card-block row">
        <div class="card-text">
          {{ form_widget(<?php echo $entityVar; ?>Form) }}
        </div>
      </div>
      <div class="card-footer">
        <a href="{{ path('admin_<?php echo $entitySnake; ?>_index') }}" class="btn btn-outline-secondary">
          {{ 'Cancel'|trans({}, 'Admin.Actions') }}
        </a>
        <button class="btn btn-primary float-right" id="save-button">
          {{ 'Save'|trans({}, 'Admin.Actions') }}
        </button>
      </div>
    </div>
  {{ form_end(<?php echo $entityVar; ?>Form) }}
{% endblock %}