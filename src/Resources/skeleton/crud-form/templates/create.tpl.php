{% extends 'PrestaShopBundle:Admin:layout.html.twig' %}

{% block content %}
  <div class="row justify-content-center">
    <div class="col">
      {# TODO: Set template domain #}
      {% include '@TemplateDomain/<?= $entity_class_name; ?>/Blocks/form.html.twig' %}
    </div>
  </div>
{% endblock %}

{% block javascripts %}
  {{ parent() }}

  {# TODO: Set the path to javascript file to enable some components if needed #}
  {# <script src="{{ asset('path/to/js') }}"></script> #}
{% endblock %}