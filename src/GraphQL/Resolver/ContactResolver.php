<?php

namespace App\GraphQL\Resolver;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;

class ContactResolver
{
    private ContactRepository $contactRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ContactRepository $contactRepository,
        EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger
    ) {
        $this->contactRepository = $contactRepository;
        $this->entityManager = $entityManager;
    }

    public function resolveContacts(): array
    {
        return $this->contactRepository->findAll();
    }

    public function resolveContact(array $args): ?Contact
    {
        return $this->contactRepository->find($args['id']);
    }

    public function resolveContactCount(): int
    {
        return $this->contactRepository->count([]);
    }

    public function resolveCreateContact(array $args): Contact
    {
        $contact = new Contact();
        $contact->setName($args['name']);
        $contact->setEmail($args['email'] ?? null);
        $contact->setPhone($args['phone'] ?? null);
        $contact->setTitle($args['title'] ?? null);
        $contact->setIsPrimary($args['isPrimary'] ?? false);

        $customer = $this->entityManager->getRepository(\App\Entity\Customer::class)->find($args['customerId']);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }
        $contact->setCustomer($customer);

        $this->entityManager->persist($contact);
        $this->entityManager->flush();
        $this->activityLogger->log(
            'contact',
            'created',
            $contact->getId(),
            sprintf('Contact "%s" was created for customer "%s".', $contact->getName(), $customer->getName())
        );

        return $contact;
    }

    public function resolveUpdateContact(array $args): Contact
    {
        $contact = $this->contactRepository->find($args['id']);

        if (!$contact) {
            throw new \Exception('Contact not found');
        }

        if (isset($args['name'])) {
            $contact->setName($args['name']);
        }
        if (isset($args['email'])) {
            $contact->setEmail($args['email']);
        }
        if (isset($args['phone'])) {
            $contact->setPhone($args['phone']);
        }
        if (isset($args['title'])) {
            $contact->setTitle($args['title']);
        }
        if (isset($args['isPrimary'])) {
            $contact->setIsPrimary($args['isPrimary']);
        }

        $this->entityManager->flush();
        $this->activityLogger->log(
            'contact',
            'updated',
            $contact->getId(),
            sprintf('Contact "%s" was updated.', $contact->getName())
        );

        return $contact;
    }

    public function resolveDeleteContact(array $args): bool
    {
        $contact = $this->contactRepository->find($args['id']);

        if (!$contact) {
            return false;
        }

        $contactId = $contact->getId();
        $contactName = $contact->getName();

        $this->entityManager->remove($contact);
        $this->entityManager->flush();
        $this->activityLogger->log(
            'contact',
            'deleted',
            $contactId,
            sprintf('Contact "%s" was deleted.', $contactName)
        );

        return true;
    }
}
