document.addEventListener('DOMContentLoaded', () => {

//TABS

const TabsNav=document.getElementsByClassName("TabsNav ProfileNav")[0];
const Tabs=TabsNav.getElementsByClassName("NavItem");

[...Tabs].forEach(tab => {

    tab.addEventListener("click",()=>{
        let ActiveTab=TabsNav.getElementsByClassName("Active")[0];
        ActiveTab.classList.remove("Active");
        tab.classList.add("Active");


        //set all other tabs content to hidden
        let TabsContent=document.getElementsByClassName("TabContent");
        [...TabsContent].forEach(TabContent => {

            TabContent.classList.add("hidden");
        });

        //get tab content id from attribute
        let TabContentId=tab.getAttribute("tab-content");
        let TabContent=document.getElementById(TabContentId);
        TabContent.classList.remove("hidden");

        TabContent.classList.remove("hidden");



    });

});



});