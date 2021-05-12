<?php
/**
 * @file
 * Contains \Drupal\resume\Form\ResumeForm.
 */
namespace Drupal\user_membership\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class UserMembershipForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_membership_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['user_id'] = [
       '#type' => 'entity_autocomplete',
			 '#title' => t('Search By User Name:'),
        '#target_type' => 'user',
				'#required' => TRUE,
      ];

    $form['membership'] = array (
      '#type' => 'date',
      '#title' => t('Member Expiration date: '),
      '#required' => TRUE,
			'#attributes'=>array('class'=>array('usermembership')),
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
		 $tmp = $form_state->getValue('membership');
		 $expiery_date = strtotime($tmp);
     \Drupal::database()->merge('uc_roles_expirations')
	     ->key(array('uid' => $user_id))
	     ->insertFields(array(
		   'uid' => $user_id,
		   'rid' => 'current_paid_member',
		   'expiration' => $expiery_date,
			 'notified' => '0',
	   ))
	  ->updateFields(array(
		  'expiration' => $expiery_date,
			 'rid' => 'current_paid_member',
	  ))->execute();
		drupal_set_message("record update save succesfully");
    }
     
 
   }