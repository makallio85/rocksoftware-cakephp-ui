<?php
declare(strict_types=1);

namespace TestApp\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * A fictional sender's settings, for the form on the components page.
 */
class DemoSettingsForm extends Form
{
    /**
     * @param \Cake\Form\Schema $schema The schema.
     * @return \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema
            ->addField('sender_name', 'string')
            ->addField('send_after', 'string')
            ->addField('reply_address', 'string')
            ->addField('is_sending_enabled', 'boolean');
    }

    /**
     * @param \Cake\Validation\Validator $validator The validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator->email('reply_address', false, 'Enter a valid email address.');
    }
}
