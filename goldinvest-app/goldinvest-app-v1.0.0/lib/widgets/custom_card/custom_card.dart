import 'package:flutter/material.dart';

class SVGDesignPainter extends CustomPainter {
  final Color backgroundColor;
  final Color foregroundColor;
  final Color semiTransparentWhite1;
  final Color semiTransparentWhite2;
  final Color circleColor;

  // Variables for text fields
  final String planDuration;
  final String planTitle;
  final String planSubtitle;
  final String planDiscountPrice;
  final String planActualPrice;
  final String planFixedProfit;
  final String planPercentage;

  SVGDesignPainter({
    required this.backgroundColor,
    required this.foregroundColor,
    required this.semiTransparentWhite1,
    required this.semiTransparentWhite2,
    required this.circleColor,
    required this.planDuration,
    required this.planTitle,
    required this.planSubtitle,
    required this.planDiscountPrice,
    required this.planActualPrice,
    required this.planFixedProfit,
    required this.planPercentage,
  });

  @override
  void paint(Canvas canvas, Size size) {
    // Get the center of the canvas
    final centerX = size.width / 2;
    final centerY = size.height / 2.5;

    // Paint background rectangle
    Paint paintWhite = Paint()..color = backgroundColor;

    canvas.drawRect(const Rect.fromLTWH(156, 95, 320, 380), paintWhite);

    // Paint the red path on the top
    Path redPath = Path();
    redPath.moveTo(centerX + 140, centerY - 170);
    redPath.lineTo(centerX - 140, centerY - 170);
    redPath.lineTo(centerX - 140, centerY - 60);
    redPath.cubicTo(centerX - 30, centerY - 15, centerX + 30, centerY - 16,
        centerX + 140, centerY - 60);
    redPath.lineTo(centerX + 140, centerY - 170);
    redPath.close();

    Paint paintRedPath = Paint()..color = foregroundColor;
    canvas.drawPath(redPath, paintRedPath);

    // Paint the white circle in the middle
    Paint whiteCirclePaint = Paint()..color = circleColor;
    canvas.drawCircle(Offset(centerX, centerY - 30), 60, whiteCirclePaint);

    // Paint the semi-transparent white circles
    Paint semiTransparentCirclePaint = Paint()..color = semiTransparentWhite1;
    canvas.drawCircle(
        Offset(centerX - 80, centerY - 120), 50.5, semiTransparentCirclePaint);

    semiTransparentCirclePaint.color = semiTransparentWhite2;
    canvas.drawCircle(
        Offset(centerX + 65, centerY - 145), 26.5, semiTransparentCirclePaint);

    // Paint the small red circles with white strokes
    Paint smallRedCirclePaint = Paint()
      ..color = foregroundColor
      ..style = PaintingStyle.fill;
    Paint smallWhiteStroke = Paint()
      ..color = circleColor
      ..style = PaintingStyle.stroke;

    canvas.drawCircle(
        Offset(centerX - 87, centerY - 37), 17, smallRedCirclePaint);
    canvas.drawCircle(Offset(centerX - 87, centerY - 37), 17,
        smallWhiteStroke..strokeWidth = 3);

    canvas.drawCircle(
        Offset(centerX + 75, centerY - 37), 10.1081, smallRedCirclePaint);
    canvas.drawCircle(Offset(centerX + 75, centerY - 37), 10.1081,
        smallWhiteStroke..strokeWidth = 1.78378);

    canvas.drawCircle(
        Offset(centerX + 100, centerY - 22), 4.13514, smallRedCirclePaint);
    canvas.drawCircle(Offset(centerX + 100, centerY - 22), 4.13514,
        smallWhiteStroke..strokeWidth = 0.72973);

    canvas.drawCircle(
        Offset(centerX - 69, centerY + 2), 7.13514, smallRedCirclePaint);
    canvas.drawCircle(Offset(centerX - 69, centerY + 2), 7.13514,
        smallWhiteStroke..strokeWidth = 0.72973);

    // ** Paint the bottom-only circular gradient around the middle circle **
    Rect gradientRect = Rect.fromCircle(
      center: Offset(centerX, centerY - 30),
      radius: 120, // Adjust the radius for the gradient area
    );
    Paint gradientPaint = Paint()
      ..shader = RadialGradient(
        colors: [
          circleColor.withValues(alpha: 0),
          const Color.fromARGB(255, 190, 5, 5).withValues(alpha: 0.8),
        ],
        stops: const [0.6, 1],
        radius: 5.0,
      ).createShader(gradientRect);

    // Clip the gradient to apply only to the bottom side
    Path clipPath = Path();
    clipPath.addOval(
        Rect.fromCircle(center: Offset(centerX, centerY - 30), radius: 60));
    clipPath.addRect(Rect.fromLTWH(centerX - 60, centerY - 30, 120, 60));
    canvas.save();
    canvas.clipPath(clipPath); // Clip to bottom half of the circle
    canvas.drawCircle(Offset(centerX, centerY - 30), 120, gradientPaint);
    canvas.restore();

    // Draw text and icons relative to the center
    _drawIconAndText(
      canvas,
      Icons.calendar_today, // Icon for plan duration
      planDuration,
      Offset(centerX - 110, centerY + 60),
      const TextStyle(color: Colors.black, fontSize: 14),
    );

    _drawText(
      canvas,
      planTitle,
      Offset(centerX - 79, centerY - 150),
      const TextStyle(
          color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
    );

    _drawText(
      canvas,
      planSubtitle,
      Offset(centerX - 90, centerY - 120),
      const TextStyle(color: Colors.white, fontSize: 14),
    );

    _drawText(
      canvas,
      planDiscountPrice,
      Offset(centerX - 45, centerY - 55),
      const TextStyle(
          color: Colors.black, fontSize: 24, fontWeight: FontWeight.bold),
    );

    _drawText(
      canvas,
      planActualPrice,
      Offset(centerX - 25, centerY - 27),
      const TextStyle(
        color: Colors.grey,
        fontSize: 16,
      ),
    );

    _drawIconAndText(
      canvas,
      Icons.monetization_on, // Icon for fixed profit
      planFixedProfit,
      Offset(centerX - 110, centerY + 90),
      const TextStyle(color: Colors.black, fontSize: 14),
    );

    _drawIconAndText(
      canvas,
      Icons.percent, // Icon for percentage profit
      planPercentage,
      Offset(centerX - 110, centerY + 120),
      const TextStyle(color: Colors.black, fontSize: 14),
    );
  }

  void _drawIconAndText(Canvas canvas, IconData icon, String text,
      Offset offset, TextStyle style) {
    final iconPainter = TextPainter(
      text: TextSpan(
        text: String.fromCharCode(icon.codePoint),
        style: TextStyle(
          fontSize: 18.0,
          fontFamily: icon.fontFamily,
          package: icon.fontPackage,
          color: Colors.black, // Icon color
        ),
      ),
      textDirection: TextDirection.ltr,
    );
    iconPainter.layout();
    iconPainter.paint(canvas, offset);

    // Offset the text slightly to the right of the icon
    _drawText(canvas, text, Offset(offset.dx + 24, offset.dy), style);
  }

  // Helper function to draw text
  void _drawText(Canvas canvas, String text, Offset offset, TextStyle style) {
    final textSpan = TextSpan(text: text, style: style);
    final textPainter = TextPainter(
      text: textSpan,
      textDirection: TextDirection.ltr,
    );
    textPainter.layout();
    textPainter.paint(canvas, offset);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) {
    return false;
  }
}
