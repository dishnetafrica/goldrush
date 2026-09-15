part of 'profile_screen.dart';

class ProfileMobileScreenLayout extends StatelessWidget {
  ProfileMobileScreenLayout({super.key});
  final formKey = GlobalKey<FormState>();
  final deliveryAddressController = Get.put(DeliveryAddressController());
  final controller = Get.put(ProfileController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: PrimaryAppBar(
        Strings.profile,
        backgroundColor: CustomColor.whiteColor,
        leading: BackButtonWidget(
          onTap: () => Get.toNamed(Routes.home),
        ),
        action: [
          Padding(
            padding: EdgeInsets.all(Dimensions.paddingSize * 0.24),
            child: Container(
              decoration: BoxDecoration(
                  color: CustomColor.primaryLightColor,
                  borderRadius: BorderRadius.circular(Dimensions.radius)),
              child: TextButton(
                  onPressed: () {
                    showDialog(
                        context: context,
                        builder: (BuildContext context) {
                          return AlertDialog(
                            title: const TitleHeading2Widget(
                                text: Strings.deleteConfirmation),
                            content: const TitleHeading4Widget(
                                text: Strings.confirmDelete),
                            actions: [
                              TextButton(
                                  onPressed: () {
                                    Navigator.of(context).pop();
                                  },
                                  child: const TitleHeading4Widget(
                                    text: Strings.cancel,
                                    color: CustomColor.blackColor,
                                  )),
                              Obx(
                                () => controller.isProfileDeleteLoading
                                    ? const CustomLoadingAPI()
                                    : TextButton(
                                        onPressed: () {
                                          controller.onProfileDelete();
                                        },
                                        child: const TitleHeading4Widget(
                                          text: Strings.delete,
                                        )),
                              )
                            ],
                          );
                        });
                  },
                  child: TitleHeading3Widget(
                    text: Strings.delete,
                    color: CustomColor.whiteColor,
                    fontSize: Dimensions.headingTextSize3 * 0.7,
                  )),
            ),
          ),
        ],
      ),
      body: Obx(
        () => controller.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context),
      ),
    );
  }

  ListView _bodyWidget(BuildContext context) {
    return ListView(
      children: [_profileInfoWidget(context)],
    );
  }

  ListView _profileInfoWidget(context) {
    final w = MediaQuery.of(context).size.width;
    return ListView(
      padding: EdgeInsets.all(Dimensions.paddingSize * 1.5),
      physics: const NeverScrollableScrollPhysics(),
      shrinkWrap: true,
      children: [
        Stack(
          children: [
            Container(
              alignment: Alignment.center,
              child: controller.imagePath.value != ''
                  ? CircleAvatar(
                      backgroundColor: CustomColor.backgroundColor,
                      radius: Dimensions.radius * 8,
                      backgroundImage:
                          FileImage(File(controller.imagePath.value)),
                    )
                  : CircleAvatar(
                      backgroundColor: CustomColor.backgroundColor,
                      backgroundImage: NetworkImage(
                        controller.userImage.value,
                      ),
                      radius: Dimensions.radius * 8,
                    ),
            ),
            Positioned(
              bottom: Dimensions.heightSize * 1.3,
              right: w * 0.18,
              child: Container(
                padding: const EdgeInsets.all(3),
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white,
                ),
                child: ImagePickerWidget(
                  child: CircleAvatar(
                    backgroundColor: CustomColor.primaryLightColor,
                    radius: Dimensions.radius * 1.5,
                    child: ClipOval(
                      child: CustomImageWidget(
                        path: Assets.icon.camera,
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
        _inputFieldWidget(context),
        _buttonWidget(context)
      ],
    );
  }

  Padding _inputFieldWidget(context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: PrimaryInputWidget(
                  textController: controller.firstNameController,
                  validator: true,
                  hintText: Strings.firstName,
                  prefixIconPath: Assets.icon.user,
                  textInputType: TextInputType.emailAddress,
                ),
              ),
              horizontalSpace(Dimensions.widthSize),
              Expanded(
                child: PrimaryInputWidget(
                  textController: controller.lastNameController,
                  validator: true,
                  hintText: Strings.lastName,
                  prefixIconPath: Assets.icon.user,
                  textInputType: TextInputType.emailAddress,
                ),
              ),
            ],
          ),
          PrimaryInputWidget(
            textController: controller.phoneNumberController,
            validator: true,
            hintText: Strings.phoneNumber,
            prefixIconPath: Assets.icon.call,
            textInputType: TextInputType.emailAddress,
          ),
          Padding(
            padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
            child: CustomDropdownMenu<Country>(
              hintText: Strings.selectCountry,
              iconPath: Assets.icon.global,
              itemsList: deliveryAddressController.sendingCountryInfo,
              selectMethod: controller.selectCountry,
              onChanged: (value) {
                controller.selectCountry.value = value!.name;
                deliveryAddressController.countryId.value = value.id;
                controller.phoneCode.value = value.mobileCode;

                deliveryAddressController.stateInfoProcess();
              },
            ),
          ),
          verticalSpace(Dimensions.widthSize * 1.5),
          Obx(
            () => deliveryAddressController.isStateLoading
                ? const CustomLoadingAPI()
                : Row(
                    children: [
                      Expanded(
                        child: CustomDropdownMenu<statesModel.State>(
                          hintText: Strings.state,
                          iconPath: Assets.icon.map,
                          itemsList: deliveryAddressController.sendingStateInfo,
                          selectMethod: controller.selectState,
                          onChanged: (value) {
                            deliveryAddressController.selectState.value =
                                value!.name;
                            deliveryAddressController.stateId.value = value.id;

                            deliveryAddressController.cityInfoProcess();
                          },
                        ),
                      ),
                      horizontalSpace(Dimensions.heightSize),
                      Expanded(
                          child: Obx(
                        () => deliveryAddressController.isCitiesLoading
                            ? const CustomLoadingAPI()
                            : CustomDropdownMenu<citiesModel.City>(
                                hintText: Strings.city,
                                iconPath: Assets.icon.pictureFrame,
                                itemsList:
                                    deliveryAddressController.sendingCityInfo,
                                selectMethod: controller.selectCity,
                                onChanged: (value) {
                                  deliveryAddressController.selectCity.value =
                                      value!.name;
                                },
                              ),
                      ))
                    ],
                  ),
          ),
          Row(
            children: [
              Expanded(
                child: PrimaryInputWidget(
                  textController: controller.zipCodeController,
                  hintText: Strings.zipCode,
                  prefixIconPath: Assets.icon.gallery,
                  textInputType: TextInputType.number,
                ),
              ),
              horizontalSpace(Dimensions.widthSize),
              Expanded(
                child: PrimaryInputWidget(
                  textController: controller.addressController,
                  hintText: Strings.address,
                  prefixIconPath: Assets.icon.gps,
                  textInputType: TextInputType.text,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Padding _buttonWidget(context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical,
      ),
      child: Column(
        children: [
          Obx(
            () => controller.isProfileUpdateLoading
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: Strings.updateProfile,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      controller.onProfileUpdateInfo();
                    },
                    buttonTextColor: CustomColor.whiteColor,
                    buttonColor: CustomColor.primaryLightColor,
                    elevation: 0,
                    borderColor: Theme.of(context).primaryColor,
                    borderWidth: 1.5,
                    radius: Dimensions.radius * 1.2,
                    height: Dimensions.heightSize * 4.67,
                  ),
          ),
        ],
      ),
    );
  }
}
