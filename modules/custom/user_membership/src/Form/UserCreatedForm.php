<?php
/**
 * @file
 * Contains \Drupal\resume\Form\ResumeForm.
 */
namespace Drupal\user_membership\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class UserCreatedForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_created_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['user_id'] = [
       '#type' => 'entity_autocomplete',
			 '#title' => t('User Name:'),
        '#target_type' => 'user',
				'#required' => TRUE,
      ];

    $form['created'] = array (
      '#type' => 'date',
      '#title' => t('Created Date'),
      '#required' => TRUE,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
    public function validateForm(array &$form, FormStateInterface $form_state) {


    }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
     
		 $user_id = $form_state->getValue('user_id');
		 $tmp = $form_state->getValue('created');
		 $expiery_date = strtotime($tmp);
		 //Load user and update there created date
		 $user = \Drupal\user\Entity\User::load($user_id);
		 if ($user) {
		   $user->set('created',$expiery_date);
			 $user->save();
			 drupal_set_message("record update save succesfully");
		 }
	}
 
}