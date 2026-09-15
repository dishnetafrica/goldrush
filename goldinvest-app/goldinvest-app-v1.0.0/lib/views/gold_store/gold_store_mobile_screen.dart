part of 'gold_store_screen.dart';

class GoldStoreMobileScreen extends StatelessWidget {
  GoldStoreMobileScreen({super.key});
  final goldInfoController = Get.put(GoldStoreController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _bodyWidget(context),
    );
  }

  Column _bodyWidget(BuildContext context) {
    return Column(
      children: [_goldBarInfoWidget(context), _buttonWidget(context)],
    );
  }

  Container _goldBarInfoWidget(context) {
    final screenHeight = MediaQuery.of(context).size.height;
    return Container(
      color: CustomColor.blackColor.withValues(alpha: 0.05),
      child: Padding(
        padding: EdgeInsets.only(
          top: Dimensions.paddingSize * 1.5,
        ),
        child: Container(
          decoration: BoxDecoration(
            boxShadow: [
              BoxShadow(
                color: CustomColor.blackColor.withValues(alpha: 0.07),
                spreadRadius: 0.5,
              ),
            ],
            color: CustomColor.whiteColor,
            borderRadius: BorderRadius.only(
              topRight: Radius.circular(Dimensions.radius * 2),
              topLeft: Radius.circular(Dimensions.radius * 2),
            ),
          ),
          child: SizedBox(
            height: screenHeight * 0.6,
            child: Obx(
              () => goldInfoController.isLoading
                  ? const CustomLoadingAPI()
                  : goldInfoController.golds == null ||
                          goldInfoController.golds!.isEmpty
                      ? Center(
                          child: Text(
                            DynamicLanguage.key(Strings.noDataFound),
                            style: TextStyle(
                                fontSize: Dimensions.headingTextSize4,
                                color: CustomColor.primaryLightTextColor,
                                fontWeight: FontWeight.w700),
                          ),
                        )
                      : ListView.builder(
                          itemCount: goldInfoController.golds!.length,
                          itemBuilder: (context, index) {
                            var golds = goldInfoController.golds;

                            return Obx(
                              () => GestureDetector(
                                onTap: () {
                                  goldInfoController.selectSlug.value =
                                      golds![index].slug;
                                  goldInfoController.selectedIndex.value =
                                      index;
                                },
                                child: Padding(
                                  padding: EdgeInsets.only(
                                      top: Dimensions.paddingSize,
                                      left: Dimensions.paddingSize,
                                      right: Dimensions.paddingSize),
                                  child: Container(
                                    decoration: BoxDecoration(
                                        gradient: LinearGradient(
                                          colors: [
                                            CustomColor.orangeColor
                                                .withValues(alpha: 0.3),
                                            CustomColor.whiteColor
                                                .withValues(alpha: 0.2),
                                            CustomColor.pinkColor
                                                .withValues(alpha: 0.5)
                                          ],
                                          begin: Alignment
                                              .topLeft, // Start position
                                          end: Alignment.bottomRight,
                                        ),
                                        borderRadius: BorderRadius.circular(
                                            Dimensions.radius),
                                        color: goldInfoController
                                                    .selectedIndex.value ==
                                                index
                                            ? CustomColor.redColor
                                            : CustomColor.blackColor
                                                .withValues(alpha: 0.1)),
                                    height: Dimensions.heightSize * 20,
                                    width: Dimensions.widthSize * 25.1,
                                    child:
                                        _cardInfoWidget(context, golds, index),
                                  ),
                                ),
                              ),
                            );
                          }),
            ),
          ),
        ),
      ),
    );
  }

  Padding _cardInfoWidget(context, List<Gold>? golds, int index) {
    return Padding(
      padding: EdgeInsets.only(
        top: Dimensions.paddingSize * 0.05,
        left: Dimensions.paddingSize,
        right: Dimensions.paddingSize,
      ),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          Row(
            mainAxisAlignment: mainSpaceBet,
            children: [
              Column(
                crossAxisAlignment: crossStart,
                children: [
                  Container(
                    padding: EdgeInsets.symmetric(
                        horizontal: Dimensions.heightSize * 0.8,
                        vertical: Dimensions.widthSize * 0.3),
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                        color: goldInfoController.selectedIndex.value == index
                            ? CustomColor.primaryLightColor
                            : CustomColor.liteBlack2,
                        borderRadius:
                            BorderRadius.circular(Dimensions.radius * 1.2)),
                    child: Center(
                      child: TitleHeading5Widget(
                        text: golds![index].title,
                        color: CustomColor.whiteColor,
                      ),
                    ),
                  ),
                  Row(
                    children: [
                      Padding(
                        padding:
                            EdgeInsets.only(top: Dimensions.paddingSize * 0.3),
                        child: TitleHeading2Widget(
                            text:
                                "\$ ${golds[index].price.toStringAsFixed(2)}"),
                      ),
                      Padding(
                        padding:
                            EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
                        child: TitleHeading2Widget(
                          text: "/ 1${golds[index].type}",
                          fontSize: Dimensions.headingTextSize6,
                          color: CustomColor.liteBlack1,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              goldInfoController.goldImage.isNotEmpty
                  ? Padding(
                      padding:
                          EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
                      child: Image.network(
                        "${goldInfoController.goldStoreModel.data.baseUrl}/${goldInfoController.goldStoreModel.data.imagePath}/${golds[index].image}",
                        height: MediaQuery.of(context).size.height * 0.1,
                        width: MediaQuery.of(context).size.width * 0.2,
                      ))
                  : Padding(
                      padding:
                          EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
                      child: Shimmer.fromColors(
                        baseColor: Colors.grey.shade300,
                        highlightColor: Colors.grey.shade100,
                        child: Container(
                          height: Dimensions.heightSize * 7,
                          width: Dimensions.widthSize * 10,
                          color: Colors.white,
                        ),
                      ),
                    ),
            ],
          ),
          Divider(
            height: Dimensions.heightSize * 0.01,
            color: CustomColor.blackColor.withValues(alpha: 0.05),
            thickness: 1,
          ),
          CustomTitleRow(
            title: golds[index].weight,
            textColor: goldInfoController.selectedIndex.value == index
                ? CustomColor.primaryLightTextColor
                : CustomColor.liteBlack1,
            tickColor: goldInfoController.selectedIndex.value == index
                ? CustomColor.primaryLightColor
                : CustomColor.liteBlack2,
          ),
          CustomTitleRow(
            title: "${DynamicLanguage.key(Strings.type)} ${golds[index].type}",
            textColor: goldInfoController.selectedIndex.value == index
                ? CustomColor.primaryLightTextColor
                : CustomColor.liteBlack1,
            tickColor: goldInfoController.selectedIndex.value == index
                ? CustomColor.primaryLightColor
                : CustomColor.liteBlack2,
          ),
          CustomTitleRow(
            title:
                "${DynamicLanguage.key(Strings.purity)} ${golds[index].purity}",
            textColor: goldInfoController.selectedIndex.value == index
                ? CustomColor.primaryLightTextColor
                : CustomColor.liteBlack1,
            tickColor: goldInfoController.selectedIndex.value == index
                ? CustomColor.primaryLightColor
                : CustomColor.liteBlack2,
          ),
          CustomTitleRow(
            title:
                "${DynamicLanguage.key(Strings.manufacturer)} ${golds[index].manufacturer}",
            textColor: goldInfoController.selectedIndex.value == index
                ? CustomColor.primaryLightTextColor
                : CustomColor.liteBlack1,
            tickColor: goldInfoController.selectedIndex.value == index
                ? CustomColor.primaryLightColor
                : CustomColor.liteBlack2,
          ),
          CustomTitleRow(
            title:
                "${DynamicLanguage.key(Strings.countryOfOrigin)} ${golds[index].countryOfOrigin}",
            textColor: goldInfoController.selectedIndex.value == index
                ? CustomColor.primaryLightTextColor
                : CustomColor.liteBlack1,
            tickColor: goldInfoController.selectedIndex.value == index
                ? CustomColor.primaryLightColor
                : CustomColor.liteBlack2,
          ),
        ],
      ),
    );
  }

  RenderObjectWidget _buttonWidget(BuildContext context) {
    return goldInfoController.golds != null
        ? Column(
            children: [
              Padding(
                padding: EdgeInsets.only(
                  left: Dimensions.paddingSize,
                  right: Dimensions.paddingSize,
                ),
                child: PrimaryButton(
                  title: Strings.buyGold,
                  fontWeight: FontWeight.w600,
                  onPressed: () {
                    Get.toNamed(Routes.deliveryAddress);
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
          )
        : const SizedBox.shrink();
  }
}
