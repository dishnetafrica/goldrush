import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:get/get.dart';

import '../../languages/strings.dart';
import '../../utils/dimensions.dart';
import '../common/text_labels/title_heading3_widget.dart';

class ImagePickerSheet extends StatelessWidget {
  final VoidCallback fromCamera, fromGallery;
  const ImagePickerSheet({
    super.key,
    required this.fromCamera,
    required this.fromGallery,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          mainAxisSize: MainAxisSize.min,
          children: [
            InkWell(
              onTap: fromGallery,
              child: Animate(
                effects: const [FadeEffect(), ScaleEffect()],
                child: Container(
                  alignment: Alignment.center,
                  padding: EdgeInsets.all(Dimensions.paddingSize * 0.5),
                  decoration: BoxDecoration(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 1.4),
                    color: Theme.of(context).colorScheme.surface,
                  ),
                  child: Column(
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(8.0),
                        child: Icon(
                          Icons.image,
                          color: Theme.of(context).primaryColor,
                          size: Dimensions.iconSizeLarge,
                        ),
                      ),
                      TitleHeading3Widget(
                        text: Strings.fromGallery,
                        color: Theme.of(context).primaryColor,
                      )
                    ],
                  ),
                ),
              ),
            ),
            // horizontalSpace(Dimensions.widthSize * 4),
            InkWell(
              onTap: fromCamera,
              child: Animate(
                effects: const [FadeEffect(), ScaleEffect()],
                child: Container(
                  alignment: Alignment.center,
                  padding: EdgeInsets.all(Dimensions.paddingSize * 0.5),
                  decoration: BoxDecoration(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 1.4),
                    color: Theme.of(context).colorScheme.surface,
                  ),
                  child: Column(
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(8.0),
                        child: Icon(
                          Icons.camera_alt,
                          color: Theme.of(context).primaryColor,
                          size: Dimensions.iconSizeLarge,
                        ),
                      ),
                      TitleHeading3Widget(
                        text: Strings.fromCamera,
                        color: Theme.of(context).primaryColor,
                      )
                    ],
                  ),
                ),
              ),
            ),
          ],
        ).paddingSymmetric(vertical: Dimensions.widthSize * 1.2),
      ],
    );
  }
}
