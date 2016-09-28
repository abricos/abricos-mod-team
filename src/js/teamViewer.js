var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var TeamViewerWidgetExt = function(){
    };
    TeamViewerWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        team: NS.ATTRIBUTE.team,
    };
    TeamViewerWidgetExt.prototype = {
        buildTData: function(){
            return {
                id: this.get('team').get('id')
            };
        },
        
    };
    NS.TeamViewerWidgetExt = TeamViewerWidgetExt;
};