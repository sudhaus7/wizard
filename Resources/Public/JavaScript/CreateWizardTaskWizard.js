'use strict';

define([
  'jquery',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Severity',
  'TYPO3/CMS/Backend/MultiStepWizard',
  'TYPO3/CMS/Backend/Icons',
  'TYPO3/CMS/Backend/Notification',
  'TYPO3/CMS/Core/SecurityUtility',
], function($, Modal, Severity, MultiStepWizard, Icons, Notification) {
  let CreateWizardTaskWizard  = {};

  CreateWizardTaskWizard.createTask = function (table, uid) {
    let createTaskForm = new FormData();
    createTaskForm.set('wizard[sourcepid]', uid);
    MultiStepWizard.addSlide(
      's7wizard-select-template',
      'Select template page',
      '',
      Severity.info,
      'Template',
      slide => {
        MultiStepWizard.lockNextStep();
        MultiStepWizard.lockPrevStep();
        MultiStepWizard.blurCancelStep();
        fetch(TYPO3.settings.ajaxUrls.s7wizardGetTemplates, {
          method: 'GET',
          credentials: 'same-origin'
        })
          .then(response => response.text())
          .then(data => {
            const response = JSON.parse(data);
            if (response.success === false) {
              Notification.error(
                'No templates found'
              );
              return;
            }
            if (response.templates.length === 1) {
              const selectedTemplate = response.templates.pop();
              createTaskForm.set('wizard[base]', selectedTemplate.value);
              MultiStepWizard.unlockNextStep();
              MultiStepWizard.triggerStepButton('next');
            }
            slide.html(response.html);
            $('#selectTemplate', slide).on('change', () => {
              if (this.value !== '') {
                createTaskForm.set('wizard[base]', this.value);
                MultiStepWizard.unlockNextStep();
              }
            })
        })
      }
    ).addSlide(
      's7wizard-general-settings',
      'Set general settings',
      '',
      Severity.info,
      'General',
      slide => {
        fetch(TYPO3.settings.ajaxUrls.s7wizardGetGeneralFields, {
          method: 'GET',
          credentials: 'same-origin'
        })
          .then(response => response.text())
          .then(data => {
            const response = JSON.parse(data);
            slide.html(response.html);
            let form = $('form#generalWizardForm', slide)[0];

            $('input.form-control', slide).each(function () {
              this.addEventListener('keyup', function () {
                if (form.checkValidity()) {
                  $('input.form-control', slide).each(function () {
                    let formKey = 'address[' + this.name + ']';

                    createTaskForm.set(formKey, this.value);
                  });
                  MultiStepWizard.unlockNextStep();
                }
              });
            });
          })
      }
    ).addSlide(
      's7wizard-editor-settings',
      'Set up new editor',
      '',
      Severity.info,
      'Editor',
      slide => {
        fetch(TYPO3.settings.ajaxUrls.s7wizardGetEditorFields, {
          method: 'GET',
          credentials: 'same-origin'
        })
          .then(response => response.text())
          .then(data => {
            const response = JSON.parse(data);
            slide.html(response.html);
            let form = $('form#editorWizardForm', slide)[0];

            $('input.form-control', slide).each(function () {
              this.addEventListener('keyup', function () {
                if (form.checkValidity()) {
                  $('input.form-control', slide).each(function () {
                    let formKey = 'address[' + this.name + ']';

                    createTaskForm.set(formKey, this.value);
                  });
                  MultiStepWizard.unlockNextStep();
                }
              });
            });
          })
      }
    ).addSlide(
      's7wizard-template-settings',
      'Set template settings',
      '',
      Severity.info,
      'Template',
      slide => {
        const templateSettingsUrl = TYPO3.settings.ajaxUrls.s7wizardGetTemplateSettings + '&template=' + createTaskForm.get('wizard[base]');
        fetch(templateSettingsUrl, {
          method: 'GET',
          credentials: 'same-origin'
        })
          .then(response => response.text())
          .then(data => {
            const response = JSON.parse(data);
            slide.html(response.html);
            let form = $('form#templateWizardForm', slide)[0];

            $('input.form-control', slide).each(function () {
              this.addEventListener('keyup', function () {
                if (form.checkValidity()) {
                  $('input.form-control', slide).each(function () {
                    let formKey = 'address[' + this.name + ']';

                    createTaskForm.set(formKey, this.value);
                  });
                  MultiStepWizard.unlockNextStep();
                }
              });
            });
          })
      }
    ).addFinalProcessingSlide(function () {
      fetch(TYPO3.settings.ajaxUrls.wizardCreateNewTask, {
        method: 'POST',
        cache: 'no-cache',
        body: createTaskForm,
      })
        .then((response) => response.text())
        .then(function (text) {
          const data = JSON.parse(text);

          if (data.success === false) {
            Array.prototype.forEach.call(
              data.error,
              function (errorText) {
                Notification.error('ERROR', errorText);
              },
            );
            document.location = data.returnUrl;
          }

          document.location = data.redirectUrl;
          MultiStepWizard.dismiss();
        });
    })
      .done(function () {
        MultiStepWizard.show();
        MultiStepWizard.getComponent().on('wizard-dismiss', function () {
          // hard reset old wizard steps here
          createTaskForm = new FormData();
        });
      });
  }

  return CreateWizardTaskWizard;
});
