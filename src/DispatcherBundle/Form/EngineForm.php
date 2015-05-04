<?php

namespace DispatcherBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class EngineForm
{

    //generate form for a new subscriber Engine
    public function buildCreateEngineForm($form, $labServers){

        $key = md5(microtime().rand());
        //$labServers = $this->getLabServers();
        $form
            ->add('name', 'text', array('label' => 'Engine Name'))
            ->add('description', 'textarea', array('label' => 'Description'))
            ->add('contact_name', 'text', array('label' => 'Contact\'s name'))
            ->add('contact_email', 'email', array('label' => 'Contact\'s Email'))
            ->add('institution', 'text', array('label' => 'Institution'))
            ->add('address', 'text', array('label' => 'Street address'))
            ->add('city', 'text', array('label' => 'City'))
            ->add('country', 'country', array('label' => 'Country'))
            ->add('username', 'text', array('label' => 'Username'))
            ->add('password', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => true,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password')))
            ->add('api_key', 'text', array('label' => 'API Key', 'data'=> $key))
            ->add('labserverId','choice', array('label' => 'Subscribe for Lab Server',
                'choices' => $labServers))
            ->add('active', 'checkbox', array('label' => 'Active',
                'required' => false,
                'data'=> false ))
            ->add('visible_in_catalogue', 'checkbox', array('label' => 'Visible in the Catalogue',
                'required' => false, 'data'=> false))
            //->add('date', 'date', array('label' => 'Date'))
            ->add('submit','submit', array('label' => 'Add Experiment Engine', 'attr' => array('class'=>'btn btn-success')))
            ->getForm();

        return $form;
    }
    //generate form to EDIT a subscriber Engine
    private function editEngineForm($engine){

        //$labServers = $this->getLabServers();
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('update_engine'))
            ->add('id', 'text', array('label' => 'Engine ID', 'attr' => array('value'=>$engine->getId(), 'readonly' => true)))
            ->add('dateCreated', 'text', array('label' => 'Created', 'attr' => array('value'=>$engine->getDateCreated(), 'readonly' => true)))
            ->add('name', 'text', array('label' => 'Engine Name', 'attr' => array('value'=>$engine->getName(), 'readonly' => false)))
            ->add('description', 'textarea', array('label' => 'Description','data'=>$engine->getDescription()))
            ->add('contact_name', 'text', array('label' => 'Contact\'s name', 'attr' => array('value'=>$engine->getContactName(), 'readonly' => false)))
            ->add('contact_email', 'email', array('label' => 'Contact\'s Email', 'attr' => array('value'=>$engine->getContactEmail(), 'readonly' => false)))
            ->add('institution', 'text', array('label' => 'Institution', 'attr' => array('value'=>$engine->getInstitution(), 'readonly' => false)))
            ->add('address', 'text', array('label' => 'Street address', 'attr' => array('value'=>$engine->getAddress(), 'readonly' => false)))
            ->add('city', 'text', array('label' => 'City', 'attr' => array('value'=>$engine->getCity(), 'readonly' => false)))
            ->add('country', 'country', array('label' => 'Country', 'data'=> $engine->getCountry()))
            ->add('basic_auth', 'text', array('label' => 'Basic Http Authentication', 'attr' => array('value'=>$engine->getHttpAuthentication(), 'readonly' => true)))
            //->add('username', 'text', array('label' => 'Username', 'attr' => array('value'=>"", 'readonly' => false, "autocomplete" => "off")))
            //->add('password','password', array('label' => 'Password'))
            ->add('api_key', 'text', array('label' => 'API Key', 'attr' => array('value'=>$engine->getApiKey(), 'readonly' => true)))
            ->add('labserverId','text', array('label' => 'Subscribe for Lab Server (ID)', 'attr' => array('value'=>$engine->getLabServerId(), 'readonly' => true)))
            ->add('active', 'checkbox', array('label' => 'Active',
                'required' => false,
                'data'=> $engine->getActive() ))
            ->add('visible_in_catalogue', 'checkbox', array('label' => 'Visible in the Catalogue',
                'required' => false, 'data'=> $engine->getVisibleInCatalogue()))
            ->add('submit','submit', array('label' => 'Save', 'attr' => array('class'=>'btn btn-success')))
            ->getForm();
        return $form;
    }




    public function getName()
    {
        return 'ExperimentEngine';
    }

}