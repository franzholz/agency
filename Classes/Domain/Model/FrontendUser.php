<?php

declare(strict_types=1);

/*
 * This file is part of the package jambagecom/agency.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
namespace JambageCom\Agency\Domain\Model;

use DateTime;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final class FrontendUser extends AbstractEntity
{
    /**
     * @var int
     *
     * @deprecated #599 will be removed in version 7.0, use the `Gender` class instead
     */
    public const GENDER_MALE = 0;

    /**
     * @var int
     *
     * @deprecated #599 will be removed in version 7.0, use the `Gender` class instead
     */
    public const GENDER_FEMALE = 1;

    /**
     * @var int
     *
     * @deprecated #599 will be removed in version 7.0, use the `Gender` class instead
     */
    public const GENDER_DIVERSE = 2;

    /**
     * @var int
     *
     * @deprecated #599 will be removed in version 7.0, use the `Gender` class instead
     */
    public const GENDER_NOT_PROVIDED = 99;

    /**
     * @var list<self::GENDER_*>
     *
     * @deprecated #599 will be removed in version 7.0, use the `Gender` class instead
     */
    public const VALID_GENDERS = [
        self::GENDER_MALE,
        self::GENDER_FEMALE,
        self::GENDER_DIVERSE,
        self::GENDER_NOT_PROVIDED,
    ];

    protected ?\DateTime $creationDate = null;
    protected string $address = '';
    protected string $city = '';
    protected string $company = '';
    protected string $country = '';
    protected string $email = '';
    protected string $fax = '';
    protected string $firstName = '';
    protected string $image = '';
    protected string $lastName = '';
    protected DateTime $lastlogin = null;
    protected string $middleName = '';
    protected string $name = '';
    protected string $password = '';
    protected string $telephone = '';
    protected string $title = '';
    protected ObjectStorage $usergroup = null;
    protected string $username = '';
    protected string $www = '';
    protected string $zip = '';
    protected string $cnum = '';
    protected string $staticInfoCountry = '';
    protected string $zone = '';
    protected string $language = '';
    protected ?\DateTime $dateOfBirth = null;
    protected int $gender = self::GENDER_NOT_PROVIDED;
    protected int $status = 0;
    protected string $houseNo = '';
    protected string $comments = '';
    protected bool $byInvitation = false;
    protected bool $moduleSysDmailHtml = false;
    protected bool $termsAcknowledged = false;
    protected bool $hasPrivileges = false;
    protected bool $privacyPolicyAcknowledged = false;
    protected ?\DateTime $privacyPolicyDate = null;
    protected string $txAgencyPassword = '';




    public function __construct()
    {
        $this->initializeObject();
    }
    public function initializeObject(): void
    {
        $this->creationDate = new DateTime();
        $this->lastlogin = new DateTime();
        $this->dateOfBirth = new DateTime();
        $this->privacyPolicyDate = new DateTime();
        $this->usergroup = new ObjectStorage();
    }
    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }
    public function setCreationDate(\DateTime $creationDate): void
    {
        $this->creationDate = $creationDate;
    }
    public function getAddress(): string
    {
        return $this->address;
    }
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }
    public function getCity(): string
    {
        return $this->city;
    }
    public function setCity(string $city): void
    {
        $this->city = $city;
    }
    public function getCompany(): string
    {
        return $this->company;
    }
    public function setCompany(string $company): void
    {
        $this->company = $company;
    }
    public function getCountry(): string
    {
        return $this->country;
    }
    public function setCountry(string $country): void
    {
        $this->country = $country;
    }
    public function getEmail(): string
    {
        return $this->email;
    }
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
    public function getFax(): string
    {
        return $this->fax;
    }
    public function setFax(string $fax): void
    {
        $this->fax = $fax;
    }
    public function getFirstName(): string
    {
        return $this->firstName;
    }
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }
    public function getImage(): string
    {
        return $this->image;
    }
    public function setImage(string $image): void
    {
        $this->image = $image;
    }
    public function getLastName(): string
    {
        return $this->lastName;
    }
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }
    public function getLastlogin(): DateTime
    {
        return $this->lastlogin;
    }
    public function setLastlogin(DateTime $lastlogin): void
    {
        $this->lastlogin = $lastlogin;
    }
    public function getMiddleName(): string
    {
        return $this->middleName;
    }
    public function setMiddleName(string $middleName): void
    {
        $this->middleName = $middleName;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
    public function getTelephone(): string
    {
        return $this->telephone;
    }
    public function setTelephone(string $telephone): void
    {
        $this->telephone = $telephone;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
    public function getUsergroup(): ObjectStorage
    {
        return $this->usergroup;
    }
    public function setUsergroup(ObjectStorage $usergroup): void
    {
        $this->usergroup = $usergroup;
    }
    public function getUsername(): string
    {
        return $this->username;
    }
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }
    public function getWww(): string
    {
        return $this->www;
    }
    public function setWww(string $www): void
    {
        $this->www = $www;
    }
    public function getZip(): string
    {
        return $this->zip;
    }
    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }
    public function getCnum(): string
    {
        return $this->cnum;
    }
    public function setCnum(string $cnum): void
    {
        $this->cnum = $cnum;
    }
    public function getStaticInfoCountry(): string
    {
        return $this->staticInfoCountry;
    }
    public function setStaticInfoCountry(string $staticInfoCountry): void
    {
        $this->staticInfoCountry = $staticInfoCountry;
    }
    public function getZone(): string
    {
        return $this->zone;
    }
    public function setZone(string $zone): void
    {
        $this->zone = $zone;
    }
    public function getLanguage(): string
    {
        return $this->language;
    }
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }
    public function getDateOfBirth(): ?\DateTime
    {
        return $this->dateOfBirth;
    }
    public function setDateOfBirth(\DateTime $dateOfBirth): void
    {
        $this->dateOfBirth = $dateOfBirth;
    }
    public function getGender(): int
    {
        return $this->gender;
    }
    public function setGender(int $gender): void
    {
        $this->gender = $gender;
    }
    public function getStatus(): int
    {
        return $this->status;
    }
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }
    public function getHouseNo(): int
    {
        return $this->houseNo;
    }
    public function setHouseNo(int $houseNo): void
    {
        $this->houseNo = $houseNo;
    }
    public function getComments(): string
    {
        return $this->comments;
    }
    public function setComments(string $comments): void
    {
        $this->comments = $comments;
    }
    public function getByInvitation(): string
    {
        return $this->byInvitation;
    }
    public function setByInvitation(string $byInvitation): void
    {
        $this->byInvitation = $byInvitation;
    }
    public function getModuleSysDmailHtml(): bool
    {
        return $this->moduleSysDmailHtml;
    }
    public function setModuleSysDmailHtml(bool $moduleSysDmailHtml): void
    {
        $this->moduleSysDmailHtml = $moduleSysDmailHtml;
    }
    public function getTermsAcknowledged(): bool
    {
        return $this->termsAcknowledged;
    }
    public function setTermsAcknowledged(bool $termsAcknowledged): void
    {
        $this->termsAcknowledged = $termsAcknowledged;
    }
    public function getHasPrivileges(): bool
    {
        return $this->hasPrivileges;
    }
    public function setHasPrivileges(bool $hasPrivileges): void
    {
        $this->hasPrivileges = $hasPrivileges;
    }
    public function getToken(): string
    {
        return $this->token;
    }
    public function setToken(string $token): void
    {
        $this->token = $token;
    }
    public function getPrivacyPolicyAcknowledged(): string
    {
        return $this->privacyPolicyAcknowledged;
    }
    public function setPrivacyPolicyAcknowledged(string $privacyPolicyAcknowledged): void
    {
        $this->privacyPolicyAcknowledged = $privacyPolicyAcknowledged;
    }
    public function getPrivacyPolicyDate(): ?\DateTime
    {
        return $this->privacyPolicyDate;
    }
    public function setPrivacyPolicyDate(\DateTime $privacyPolicyDate): void
    {
        $this->privacyPolicyDate = $privacyPolicyDate;
    }
    public function getTxAgencyPassword(): string
    {
        return $this->txAgencyPassword;
    }
    public function setTxAgencyPassword(string $txAgencyPassword): void
    {
        $this->txAgencyPassword = $txAgencyPassword;
    }
    public function getLostPassword(): bool
    {
        return $this->lostPassword;
    }
    public function setLostPassword(bool $lostPassword): void
    {
        $this->lostPassword = $lostPassword;
    }
}


