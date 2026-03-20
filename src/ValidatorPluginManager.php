<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function array_replace_recursive;
use function assert;
use Laminas\Service_Manager\Abstract_Single_Instance_Plugin_Manager;
use Laminas\Service_Manager\Factory\Invokable_Factory;
use Laminas\Service_Manager\Service_Manager;
use Laminas\Translator\Translator_Interface;
use Psr\Container\Container_Interface;
/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @extends AbstractSingleInstancePluginManager<ValidatorInterface>
 */
final class Validator_Plugin_Manager extends Abstract_Single_Instance_Plugin_Manager
{
    private const DEFAULT_CONFIGURATION = ['factories' => [Backed_Enum_Value::class => Invokable_Factory::class, Barcode::class => Invokable_Factory::class, Bitwise::class => Invokable_Factory::class, Business_Identifier_Code::class => Invokable_Factory::class, Callback::class => Invokable_Factory::class, Conditional::class => Conditional_Factory::class, Credit_Card::class => Invokable_Factory::class, Date_Step::class => Invokable_Factory::class, Date::class => Invokable_Factory::class, Date_Comparison::class => Invokable_Factory::class, Date_Interval_String::class => Invokable_Factory::class, Digits::class => Invokable_Factory::class, Email_Address::class => Invokable_Factory::class, Enum_Case::class => Invokable_Factory::class, Explode::class => Invokable_Factory::class, File\Count::class => Invokable_Factory::class, File\Exclude_Extension::class => Invokable_Factory::class, File\Exclude_Mime_Type::class => Invokable_Factory::class, File\Exists::class => Invokable_Factory::class, File\Extension::class => Invokable_Factory::class, File\Files_Size::class => Invokable_Factory::class, File\Hash::class => Invokable_Factory::class, File\Image_Size::class => Invokable_Factory::class, File\Is_Compressed::class => Invokable_Factory::class, File\Is_Image::class => Invokable_Factory::class, File\Mime_Type::class => Invokable_Factory::class, File\Not_Exists::class => Invokable_Factory::class, File\Size::class => Invokable_Factory::class, File\Upload_File::class => Invokable_Factory::class, File\Word_Count::class => Invokable_Factory::class, Gps_Point::class => Invokable_Factory::class, Hex::class => Invokable_Factory::class, Hostname::class => Invokable_Factory::class, Host_With_Public_I_Pv4address::class => Invokable_Factory::class, Iban::class => Invokable_Factory::class, Identical::class => Invokable_Factory::class, In_Array::class => Invokable_Factory::class, Ip::class => Invokable_Factory::class, Is_Array::class => Invokable_Factory::class, Isbn::class => Invokable_Factory::class, Is_Countable::class => Invokable_Factory::class, Is_Instance_Of::class => Invokable_Factory::class, Is_Json_String::class => Invokable_Factory::class, Key_Exists::class => Invokable_Factory::class, Not_Empty::class => Invokable_Factory::class, Number_Comparison::class => Invokable_Factory::class, Regex::class => Invokable_Factory::class, Sitemap\Changefreq::class => Invokable_Factory::class, Sitemap\Lastmod::class => Invokable_Factory::class, Sitemap\Loc::class => Invokable_Factory::class, Sitemap\Priority::class => Invokable_Factory::class, String_Length::class => Invokable_Factory::class, Step::class => Invokable_Factory::class, Timezone::class => Invokable_Factory::class, Uri::class => Invokable_Factory::class, Uuid::class => Invokable_Factory::class, Validator_Chain::class => Validator_Chain_Invokable_Factory::class], 'aliases' => ['barcode' => Barcode::class, 'Barcode' => Barcode::class, 'BIC' => Business_Identifier_Code::class, 'bic' => Business_Identifier_Code::class, 'bitwise' => Bitwise::class, 'Bitwise' => Bitwise::class, 'BusinessIdentifierCode' => Business_Identifier_Code::class, 'businessidentifiercode' => Business_Identifier_Code::class, 'callback' => Callback::class, 'Callback' => Callback::class, 'creditcard' => Credit_Card::class, 'creditCard' => Credit_Card::class, 'CreditCard' => Credit_Card::class, 'date' => Date::class, 'Date' => Date::class, 'datestep' => Date_Step::class, 'dateStep' => Date_Step::class, 'DateStep' => Date_Step::class, 'digits' => Digits::class, 'Digits' => Digits::class, 'emailaddress' => Email_Address::class, 'emailAddress' => Email_Address::class, 'EmailAddress' => Email_Address::class, 'explode' => Explode::class, 'Explode' => Explode::class, 'filecount' => File\Count::class, 'fileCount' => File\Count::class, 'FileCount' => File\Count::class, 'fileexcludeextension' => File\Exclude_Extension::class, 'fileExcludeExtension' => File\Exclude_Extension::class, 'FileExcludeExtension' => File\Exclude_Extension::class, 'fileexcludemimetype' => File\Exclude_Mime_Type::class, 'fileExcludeMimeType' => File\Exclude_Mime_Type::class, 'FileExcludeMimeType' => File\Exclude_Mime_Type::class, 'fileexists' => File\Exists::class, 'fileExists' => File\Exists::class, 'FileExists' => File\Exists::class, 'fileextension' => File\Extension::class, 'fileExtension' => File\Extension::class, 'FileExtension' => File\Extension::class, 'filefilessize' => File\Files_Size::class, 'fileFilesSize' => File\Files_Size::class, 'FileFilesSize' => File\Files_Size::class, 'filehash' => File\Hash::class, 'fileHash' => File\Hash::class, 'FileHash' => File\Hash::class, 'fileimagesize' => File\Image_Size::class, 'fileImageSize' => File\Image_Size::class, 'FileImageSize' => File\Image_Size::class, 'fileiscompressed' => File\Is_Compressed::class, 'fileIsCompressed' => File\Is_Compressed::class, 'FileIsCompressed' => File\Is_Compressed::class, 'fileisimage' => File\Is_Image::class, 'fileIsImage' => File\Is_Image::class, 'FileIsImage' => File\Is_Image::class, 'filemimetype' => File\Mime_Type::class, 'fileMimeType' => File\Mime_Type::class, 'FileMimeType' => File\Mime_Type::class, 'filenotexists' => File\Not_Exists::class, 'fileNotExists' => File\Not_Exists::class, 'FileNotExists' => File\Not_Exists::class, 'filesize' => File\Size::class, 'fileSize' => File\Size::class, 'FileSize' => File\Size::class, 'fileuploadfile' => File\Upload_File::class, 'fileUploadFile' => File\Upload_File::class, 'FileUploadFile' => File\Upload_File::class, 'filewordcount' => File\Word_Count::class, 'fileWordCount' => File\Word_Count::class, 'FileWordCount' => File\Word_Count::class, 'gpspoint' => Gps_Point::class, 'gpsPoint' => Gps_Point::class, 'GpsPoint' => Gps_Point::class, 'hex' => Hex::class, 'Hex' => Hex::class, 'hostname' => Hostname::class, 'Hostname' => Hostname::class, 'iban' => Iban::class, 'Iban' => Iban::class, 'identical' => Identical::class, 'Identical' => Identical::class, 'inarray' => In_Array::class, 'inArray' => In_Array::class, 'InArray' => In_Array::class, 'ip' => Ip::class, 'Ip' => Ip::class, 'IsArray' => Is_Array::class, 'isbn' => Isbn::class, 'Isbn' => Isbn::class, 'isCountable' => Is_Countable::class, 'IsCountable' => Is_Countable::class, 'iscountable' => Is_Countable::class, 'isinstanceof' => Is_Instance_Of::class, 'isInstanceOf' => Is_Instance_Of::class, 'IsInstanceOf' => Is_Instance_Of::class, 'keyExists' => Key_Exists::class, 'notempty' => Not_Empty::class, 'notEmpty' => Not_Empty::class, 'NotEmpty' => Not_Empty::class, 'regex' => Regex::class, 'Regex' => Regex::class, 'sitemapchangefreq' => Sitemap\Changefreq::class, 'sitemapChangefreq' => Sitemap\Changefreq::class, 'SitemapChangefreq' => Sitemap\Changefreq::class, 'sitemaplastmod' => Sitemap\Lastmod::class, 'sitemapLastmod' => Sitemap\Lastmod::class, 'SitemapLastmod' => Sitemap\Lastmod::class, 'sitemaploc' => Sitemap\Loc::class, 'sitemapLoc' => Sitemap\Loc::class, 'SitemapLoc' => Sitemap\Loc::class, 'sitemappriority' => Sitemap\Priority::class, 'sitemapPriority' => Sitemap\Priority::class, 'SitemapPriority' => Sitemap\Priority::class, 'stringlength' => String_Length::class, 'stringLength' => String_Length::class, 'StringLength' => String_Length::class, 'step' => Step::class, 'Step' => Step::class, 'timezone' => Timezone::class, 'Timezone' => Timezone::class, 'uri' => Uri::class, 'Uri' => Uri::class, 'uuid' => Uuid::class, 'Uuid' => Uuid::class, Validator_Chain_Interface::class => Validator_Chain::class]];
    /**
     * Whether to share by default; default to false
     */
    protected bool $shared_by_default = false;
    protected string $instance_of = Validator_Interface::class;
    /**
     * @param ServiceManagerConfiguration $config
     */
    public function __construct(Container_Interface $creation_context, array $config = [])
    {
        /** @var ServiceManagerConfiguration $config */
        $config = array_replace_recursive(self::DEFAULT_CONFIGURATION, $config);
        parent::__construct($creation_context, $config);
        $this->add_initializer($this->inject_translator(...));
        $this->add_initializer($this->inject_validator_plugin_manager(...));
    }
    /** @internal */
    protected function inject_translator(Container_Interface $container, object $validator): void
    {
        if (!$validator instanceof Translator\Translator_Aware_Interface) {
            return;
        }
        if ($container->has('MvcTranslator')) {
            $translator = $container->get('MvcTranslator');
            assert($translator instanceof Translator_Interface);
            $validator->set_translator($translator);
            return;
        }
        if ($container->has(Translator_Interface::class)) {
            $validator->set_translator($container->get(Translator_Interface::class));
        }
    }
    /** @internal */
    protected function inject_validator_plugin_manager(Container_Interface $container, object $validator): void
    {
        if (!$validator instanceof Validator_Plugin_Manager_Aware_Interface) {
            return;
        }
        $validator->set_validator_plugin_manager($this);
    }
}