part of 'delivery_address_screen.dart';

// ignore: must_be_immutable
class DeliveryAddressScreen extends StatelessWidget {
  DeliveryAddressScreen({super.key});

  final deliveryAddressController = Get.put(DeliveryAddressController());
  final profileController = Get.put(ProfileController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
        appBar: PrimaryAppBar(
          backgroundColor: CustomColor.whiteColor,
          Strings.deliveryAddress,
          showBackButton: true,
          leading: BackButtonWidget(
            onTap: () => Get.to(const InvestmentScreen(selectTab: 1)),
          ),
        ),
        body: Obx(
          () => deliveryAddressController.isStateLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ));
  }

  Padding _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
          left: Dimensions.paddingSize, right: Dimensions.paddingSize),
      child: Column(
        children: [_inputFieldWidget(context), _buttonWidget(context)],
      ),
    );
  }

  final GlobalKey<FormState> formKey = GlobalKey<FormState>();

  Form _inputFieldWidget(context) {
    return Form(
      key: formKey,
      child: Column(
        children: [
          CustomDropdownMenu<Country>(
            hintText: Strings.selectCountry,
            iconPath: Assets.icon.global,
            itemsList: deliveryAddressController.sendingCountryInfo,
            selectMethod: profileController.selectCountry,
            onChanged: (value) {
              deliveryAddressController.selectCountry.value = value!.name;
              deliveryAddressController.countryId.value = value.id;
              deliveryAddressController.phoneCode.value = value.mobileCode;

              deliveryAddressController.stateInfoProcess();
            },
          ),
          PrimaryInputWidget(
            validator: true,
            textController: profileController.phoneNumberController,
            hintText: Strings.phoneNumber,
            prefixIconPath: Assets.icon.call,
            textInputType: TextInputType.number,
          ),
          Obx(
            () => deliveryAddressController.isStateLoading
                ? const CustomLoadingAPI()
                : Padding(
                    padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.1),
                    child: Row(
                      children: [
                        Expanded(
                          child: CustomDropdownMenu<statesModel.State>(
                            hintText: Strings.state,
                            iconPath: Assets.icon.map,
                            itemsList:
                                deliveryAddressController.sendingStateInfo,
                            selectMethod: profileController.selectState,
                            onChanged: (value) {
                              deliveryAddressController.selectState.value =
                                  value!.name;
                              deliveryAddressController.stateId.value =
                                  value.id;

                              deliveryAddressController.cityInfoProcess();
                            },
                          ),
                        ),
                        horizontalSpace(Dimensions.widthSize),
                        Expanded(
                          child: Obx(
                            () => deliveryAddressController.isCitiesLoading
                                ? const CustomLoadingAPI()
                                : CustomDropdownMenu<citiesModel.City>(
                                    hintText: Strings.city,
                                    iconPath: Assets.icon.pictureFrame,
                                    itemsList: deliveryAddressController
                                        .sendingCityInfo,
                                    selectMethod: profileController.selectCity,
                                    onChanged: (value) {
                                      deliveryAddressController
                                          .selectCity.value = value!.name;
                                    },
                                  ),
                          ),
                        ),
                      ],
                    ),
                  ),
          ),
          Row(
            children: [
              Expanded(
                child: PrimaryInputWidget(
                  validator: true,
                  textController: profileController.zipCodeController,
                  hintText: Strings.zipCode,
                  prefixIconPath: Assets.icon.gallery,
                  textInputType: TextInputType.emailAddress,
                ),
              ),
              horizontalSpace(Dimensions.widthSize),
              Expanded(
                child: PrimaryInputWidget(
                  validator: true,
                  textController: profileController.addressController,
                  hintText: Strings.address,
                  prefixIconPath: Assets.icon.gps,
                  textInputType: TextInputType.emailAddress,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Padding _buttonWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical,
      ),
      child: Column(
        children: [
          PrimaryButton(
            title: Strings.continue1,
            fontWeight: FontWeight.w600,
            onPressed: () {
              if (formKey.currentState!.validate() &&
                  deliveryAddressController.selectCountry.value.isNotEmpty &&
                  deliveryAddressController.selectCity.value.isNotEmpty &&
                  deliveryAddressController.selectState.value.isNotEmpty) {
                Get.toNamed(Routes.checkout);
              } else {
                CustomSnackBar.error('Please Filed the fill');
              }
            },
            buttonTextColor: CustomColor.whiteColor,
            buttonColor: CustomColor.primaryLightColor,
            elevation: 0,
            borderColor: Theme.of(context).primaryColor,
            borderWidth: 1.5,
            radius: Dimensions.radius * 1.2,
            height: Dimensions.heightSize * 4.67,
          ),
        ],
      ),
    );
  }
}
