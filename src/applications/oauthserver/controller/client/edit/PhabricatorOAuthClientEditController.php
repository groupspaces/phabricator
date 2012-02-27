<?php

/*
 * Copyright 2012 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @group oauthserver
 */
final class PhabricatorOAuthClientEditController
extends PhabricatorOAuthClientBaseController {

  private $isEdit;
  protected function isClientEdit() {
    return $this->isEdit;
  }
  private function setIsClientEdit($is_edit) {
    $this->isEdit = (bool) $is_edit;
    return $this;
  }

  protected function getExtraClientFilters() {
    if ($this->isClientEdit()) {
      $filters = array(
        array('url'   => $this->getFilter(),
              'label' => 'Edit Client')
      );
    } else {
      $filters = array();
    }
    return $filters;
  }

  public function getFilter() {
    if ($this->isClientEdit()) {
      $filter = 'client/edit/'.$this->getClientPHID();
    } else {
      $filter = 'client/create';
    }
    return $filter;
  }

  public function processRequest() {
    $request       = $this->getRequest();
    $current_user  = $request->getUser();
    $error         = null;
    $bad_redirect  = false;
    $phid          = $this->getClientPHID();
    // if we have a phid, then we're editing
    $this->setIsClientEdit($phid);

    if ($this->isClientEdit()) {
      $client = id(new PhabricatorOAuthServerClient())
        ->loadOneWhere('phid = %s',
                       $phid);
      $title  = 'Edit OAuth Client';
      // validate the client
      if (empty($client)) {
        return new Aphront404Response();
      }
      if ($client->getCreatorPHID() != $current_user->getPHID()) {
        $message = 'Access denied to edit client with id '.$phid.'. '.
                   'Only the user who created the client has permission to '.
                   'edit the client.';
        return id(new Aphront403Response())
          ->setForbiddenText($message);
      }
      $submit_button = 'Save OAuth Client';
    // new client - much simpler
    } else {
      $client = new PhabricatorOAuthServerClient();
      $title  = 'Create OAuth Client';
      $submit_button = 'Create OAuth Client';
    }

    if ($request->isFormPost()) {
      $redirect_uri = $request->getStr('redirect_uri');
      $client->setName($request->getStr('name'));
      $client->setRedirectURI($redirect_uri);
      $client->setSecret(Filesystem::readRandomCharacters(32));
      $client->setCreatorPHID($current_user->getPHID());
      $uri = new PhutilURI($redirect_uri);
      if (!$this->validateRedirectURI($uri)) {
        $error = new AphrontErrorView();
        $error->setSeverity(AphrontErrorView::SEVERITY_ERROR);
        $error->setTitle(
          'Redirect URI must be a fully qualified domain name.'
        );
        $bad_redirect = true;
      } else {
        $client->save();
        if ($this->isClientEdit()) {
          return id(new AphrontRedirectResponse())
            ->setURI('/oauthserver/client/?edited='.$phid);
        } else {
          return id(new AphrontRedirectResponse())
            ->setURI('/oauthserver/client/?new='.$phid);
        }
      }
    }

    $panel = new AphrontPanelView();
    if ($this->isClientEdit()) {
      $delete_button = phutil_render_tag(
        'a',
        array(
          'href' => $client->getDeleteURI(),
          'class' => 'grey button',
        ),
        'Delete OAuth Client');
      $panel->addButton($delete_button);
    }
    $panel->setHeader($title);

    $form = id(new AphrontFormView())
      ->setUser($current_user)
      ->appendChild(
        id(new AphrontFormTextControl())
        ->setLabel('Name')
        ->setName('name')
        ->setValue($client->getName())
      );
    if ($this->isClientEdit()) {
      $form
        ->appendChild(
          id(new AphrontFormTextControl())
          ->setLabel('ID')
          ->setValue($phid)
        )
        ->appendChild(
          id(new AphrontFormTextControl())
          ->setLabel('Secret')
          ->setValue($client->getSecret())
        );
    }
    $form
      ->appendChild(
        id(new AphrontFormTextControl())
        ->setLabel('Redirect URI')
        ->setName('redirect_uri')
        ->setValue($client->getRedirectURI())
        ->setError($bad_redirect)
      );
    if ($this->isClientEdit()) {
      $created = phabricator_datetime($client->getDateCreated(),
                                      $current_user);
      $updated = phabricator_datetime($client->getDateModified(),
                                      $current_user);
      $form
        ->appendChild(
          id(new AphrontFormStaticControl())
          ->setLabel('Created')
          ->setValue($created)
        )
        ->appendChild(
          id(new AphrontFormStaticControl())
          ->setLabel('Last Updated')
          ->setValue($updated)
        );
    }
    $form
      ->appendChild(
        id(new AphrontFormSubmitControl())
        ->setValue($submit_button)
      );

    $panel->appendChild($form);
    return $this->buildStandardPageResponse(
      array($error,
            $panel
      ),
      array('title' => $title)
    );
  }

  private function validateRedirectURI(PhutilURI $uri) {
    if (PhabricatorEnv::isValidRemoteWebResource($uri)) {
      if ($uri->getDomain()) {
        return true;
      }
    }
    return false;
  }

}
