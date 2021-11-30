<?php
require_once __DIR__ . '/vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Перекресток</title>
</head>
<body>
<h2>Настройки:</h2>
<form action="setData.php" method="post">
    <b>Светофора:</b>
    <div>
        <input id="hasCrosslight" type="checkbox" name="hasCrosslight">
        <label for="hasCrosslight">Наличие светофора</label>

        <input id="crosslightDuration" type="text" name="crosslightDuration">
        <label for="crosslightDuration">Длительность света</label>
    </div>
    <b>Дороги:</b>
    <div>
        <input type="radio" id="verticalPriority"
               name="priority" value="vertical">
        <label for="verticalPriority">Вертикаль</label>

        <input type="radio" id="horizontalPriority"
               name="priority" value="horizontal">
        <label for="horizontalPriority">Горизонталь</label>

        <input type="radio" id="notPriority"
               name="priority" value="">
        <label for="notPriority">Нет приоритета</label>
    </div>
    <b>Полос:</b>
    <div>
        <label for="verticalLaneMaxSpeed">Макс скорость вертикальных полос</label>
        <input id="verticalLaneMaxSpeed" type="text" name="verticalLaneMaxSpeed">

        <label for="horizontalLaneMaxSpeed">Макс скорость горизонтальных полос</label>
        <input id="horizontalLaneMaxSpeed" type="text" name="horizontalLaneMaxSpeed">
    </div>
    <div>
        <label for="verticalLaneTrafficIntensity">Интенсивность траффика вертикальных полос(чем меньше тем чаще)</label>
        <input id="verticalLaneTrafficIntensity" type="text" name="verticalLaneTrafficIntensity">

        <label for="horizontalLaneTrafficIntensity">Интенсивность траффика горизонтальных полос(чем меньше тем
            чаще)</label>
        <input id="horizontalLaneTrafficIntensity" type="text" name="horizontalLaneTrafficIntensity">
    </div>
    <b>Машин:</b>
    <div>
        <label for="carMaxSpeed">Макс скорость машин</label>
        <input id="carMaxSpeed" type="text" name="carMaxSpeed">
    </div>
    <button type="submit">Применить</button>
</form>
<canvas id="field" width="1200" height="1200"></canvas>
</body>
</html>
<script>
    window.settingsInited = false
    function draw(appData) {
        let canvas = document.getElementById('field');
        if (canvas.getContext) {

            if(window.settingsInited === false) {
                if (appData.crossRoad.trafficLight) {
                    document.getElementById('hasCrosslight').setAttribute('checked', 'true')
                    document.getElementById('crosslightDuration').setAttribute('value', appData.crossRoad.trafficLight.duration)
                }
                let verticalRoad = appData.crossRoad.verticalRoad
                let horizontalRoad = appData.crossRoad.horizontalRoad

                if(verticalRoad.hasPriority === true) {
                    document.getElementById('verticalPriority').setAttribute('checked', 'true')
                } else if(horizontalRoad.hasPriority === true) {
                    document.getElementById('horizontalPriority').setAttribute('checked', 'true')
                } else {
                    document.getElementById('notPriority').setAttribute('checked', 'true')
                }

                let verticalLane = verticalRoad.lanes[0]
                let horizontalLane = horizontalRoad.lanes[0]


                document.getElementById('verticalLaneMaxSpeed').setAttribute('value', verticalLane.maxSpeed)
                document.getElementById('horizontalLaneMaxSpeed').setAttribute('value', horizontalLane.maxSpeed)

                document.getElementById('verticalLaneTrafficIntensity').setAttribute('value', verticalLane.trafficIntensity)
                document.getElementById('horizontalLaneTrafficIntensity').setAttribute('value', horizontalLane.trafficIntensity)

                document.getElementById('carMaxSpeed').setAttribute('value', appData.carMaxSpeed)

                window.settingsInited = true
            }

            let ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            function renderRect(rect, color = 'black') {
                ctx.fillStyle = color;
                if (rect.transformation === 'vertical') {
                    ctx.fillRect(rect.point.x, rect.point.y, rect.width, rect.length);
                } else {
                    ctx.fillRect(rect.point.x, rect.point.y, rect.length, rect.width);
                }
            }

            function renderTrafficLight(trafficLight) {
                verticalGreenLightPosition = [452, 372, 16, 16]
                verticalRedLightPosition = [472, 372, 16, 16]

                horizontalGreenLightPosition = [352, 452, 16, 16]
                horizontalRedLightPosition = [372, 452, 16, 16]
                ctx.fillStyle = 'black'
                ctx.fillRect(350, 450, 40, 20);
                ctx.fillRect(450, 350, 20, 40);

                ctx.fillStyle = trafficLight.currentLight.verticalRoadColor

                if(trafficLight.currentLight.verticalRoadColor === 'red') {
                    ctx.fillRect(...verticalRedLightPosition)
                } else {
                    ctx.fillRect(...verticalGreenLightPosition)
                }

                ctx.fillStyle = trafficLight.currentLight.horizontalRoadColor

                if(trafficLight.currentLight.horizontalRoadColor === 'red') {
                    ctx.fillRect(...horizontalRedLightPosition)
                } else {
                    ctx.fillRect(...horizontalGreenLightPosition)
                }
            }

            let roadCollection = [appData.crossRoad.verticalRoad, appData.crossRoad.horizontalRoad]
            let greenCars = []
            let redCars = []
            if (appData.crossRoad.trafficLight) {
                let trafficLight = appData.crossRoad.trafficLight
                renderTrafficLight(trafficLight)
            }

            roadCollection.forEach(
                function (road) {
                    renderRect(road)
                    road.lanes.forEach(
                        function (lane) {
                            if (lane.isReverse) {
                                renderRect(lane, 'grey')
                                redCars.push(...lane.cars)
                            } else {
                                greenCars.push(...lane.cars)
                            }
                        }
                    )
                }
            )

            redCars.forEach(function (car) {
                switch (car.plannedManeur) {
                    case 'left':
                        renderRect(car, 'orange')
                        break
                    case 'right':
                        renderRect(car, 'purple')
                        break
                    default:
                        renderRect(car, 'red')
                }
            })

            greenCars.forEach(function (car) {
                switch (car.plannedManeur) {
                    case 'left':
                        renderRect(car, 'blue')
                        break
                    case 'right':
                        renderRect(car, 'yellow')
                        break
                    default:
                        renderRect(car, 'green')
                }
            })
        }

    }

    const timer = ms => new Promise(res => setTimeout(res, ms))

    async function load() {
        for (let i = 0; i < 1000; i++) {

            const request = new XMLHttpRequest();

            const url = "getData.php";

            request.open('GET', url);
            request.setRequestHeader('Content-Type', 'application/x-www-form-url');
            request.addEventListener("readystatechange", () => {
                if (request.readyState === 4 && request.status === 200) {
                    draw(JSON.parse(request.responseText));
                }
            });

            request.send();

            await timer(300);
        }
    }

    load();
</script>